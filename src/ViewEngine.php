<?php
namespace App\Core\PHTML;

use Exception;
use Throwable;
use ErrorException;
use InvalidArgumentException;
use Illuminate\Support\Facades\App;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Compilers\CompilerInterface;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class ViewEngine extends PhpEngine implements CompilerInterface
{
	/**
	 * The Filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The path for the view currently being compiled.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The cache path for the compiled views.
	 *
	 * @var string
	 */
	protected $cachePath;

	/**
	 * The PHTML engine instance.
	 *
	 * @var Engine
	 */
	protected $engine;

	/**
	 * A stack of the last compiled templates.
	 *
	 * @var array
	 */
	protected $lastCompiled = [];

	/**
	 * Create a new FML view engine instance.
	 *
	 * @param  Engine  $engine
	 * @return void
	 */
	public function __construct(Engine $engine, Filesystem $files, $cachePath)
	{
		$this->files = $files;
		$this->engine = $engine;
		$this->cachePath = $cachePath;
	}

	/**
	 * Get the evaluated contents of the view.
	 *
	 * @param  string  $path
	 * @param  array   $data
	 * @return string
	 */
	public function get($path, array $data = [])
	{
		$this->lastCompiled[] = $path;

		// Set the environment variable to an isolated PHTML environment
		//$env = new Environment;
		//$this->engine->setEnvironment($env);
		$view = $data['__env'];
		unset($data['app']);
		unset($data['__env']);

		// If this given view has expired, which means it has simply been edited since
		// it was last compiled, we will re-compile the views so we can evaluate a
		// fresh copy of the view. We'll pass the compiler the path of the view.
		if ($this->engine->isExpired($path)) {
			if ($this->cachePath !== null) {
				$name = App::get('view.finder')->getLastTemplateName();
				$source = new Source($this->files->get($path), $name, $path);
				$contents = $this->engine->compile($this->engine->parse($this->engine->tokenize($source)));
				$this->files->put($this->getCompiledPath($source->getPath()), $contents);
			}
		}

		$compiled = $this->getCompiledPath($path);

		// Once we have the path to the compiled file, we will evaluate the paths with
		// typical PHP just like any other templates. We also keep a stack of views
		// which have been rendered for right exception messages to be generated.
		$results = $this->evaluatePath($compiled, $data);

		array_pop($this->lastCompiled);

		return $results;
	}

	/**
	 * Get the evaluated contents of the view at the given path.
	 *
	 * @param  string  $__path
	 * @param  array   $__data
	 * @return string
	 */
	protected function evaluatePath($path, $data)
	{
		$obLevel = ob_get_level();

		ob_start();

		// We'll evaluate the contents of the view inside a try/catch block so we can
		// flush out any stray output that might get out before an error occurs or
		// an exception is thrown. This prevents any partial views from leaking.
		try {
			$this->engine->getEnvironment()->run($path, $data);
		} catch (Exception $e) {
			$this->handleViewException($e, $obLevel);
		} catch (Throwable $e) {
			$this->handleViewException(new FatalThrowableError($e), $obLevel);
		}

		return ltrim(ob_get_clean());
		//return $this->minifyHTML(ltrim(ob_get_clean()));
	}

	/**
	 * Handle a view exception.
	 *
	 * @param  \Exception  $e
	 * @param  int  $obLevel
	 * @return void
	 *
	 * @throws \Exception
	 */
	protected function handleViewException(Exception $e, $obLevel)
	{
		$e = new ErrorException($this->getMessage($e), 0, 1, $e->getFile(), $e->getLine(), $e);

		parent::handleViewException($e, $obLevel);
	}

	/**
	 * Get the exception message for an exception.
	 *
	 * @param  \Exception  $e
	 * @return string
	 */
	protected function getMessage(Exception $e)
	{
		return $e->getMessage().' (View: '.realpath(last($this->lastCompiled)).')';
	}

	/**
	 * Get the compiler implementation.
	 *
	 * @return \Illuminate\View\Compilers\CompilerInterface
	 */
	public function getCompiler()
	{
		return $this->compiler;
	}

	/**
	 * Get the path to the compiled version of a view.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function getCompiledPath($path)
	{
		return $this->cachePath.'/'.sha1($path).'.php';
	}

	/**
	 * Determine if the given view is expired.
	 *
	 * @param  string  $path
	 * @return bool
	 */
	public function isExpired($path)
	{
		$compiled = $this->getCompiledPath($path);

		if (!$this->files->exists($compiled)) {
			return true;
		}

		return 	$this->files->lastModified($path) >=
				$this->files->lastModified($compiled);
	}

	/**
	 * Compile the view at the given path.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function compile($path = null)
	{
		if ($path) {
			$this->setPath($path);
		}

		if (!is_null($this->cachePath)) {
			$contents = $this->compileString($this->files->get($this->getPath()));

			$this->files->put($this->getCompiledPath($this->getPath()), $contents);
		}
	}

	/**
	 * Get the path currently being compiled.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Set the path currently being compiled.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	private function minifyHTML($input) {
		if (trim($input) === "") return $input;
		// Remove extra white-space(s) between HTML attribute(s)
		$input = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function($matches) {
			return '<' . $matches[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2]) . $matches[3] . '>';
		}, str_replace("\r", "", $input));
		// Minify inline CSS declaration(s)
		if(strpos($input, ' style=') !== false) {
			$input = preg_replace_callback('#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', function($matches) {
				return '<' . $matches[1] . ' style=' . $matches[2] . minifyCSS($matches[3]) . $matches[2];
			}, $input);
		}
		return preg_replace(
			[
				// t = text
				// o = tag open
				// c = tag close
				// Keep important white-space(s) after self-closing HTML tag(s)
				'#<(img|input)(>| .*?>)#s',
				// Remove a line break and two or more white-space(s) between tag(s)
				'#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
				'#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s', // t+c || o+t
				'#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s', // o+o || c+c
				'#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s', // c+t || t+o || o+t -- separated by long white-space(s)
				'#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s', // empty tag
				'#<(img|input)(>| .*?>)<\/\1>#s', // reset previous fix
				'#(&nbsp;)&nbsp;(?![<\s])#', // clean up ...
				'#(?<=\>)(&nbsp;)(?=\<)#', // --ibid
				// Remove HTML comment(s) except IE comment(s)
				//'#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s'
			],
			[
				'<$1$2<$1>',
				'$1$2$3',
				'$1$2$3',
				'$1$2$3$4$5',
				'$1$2$3$4$5$6$7',
				'$1$2$3',
				'<$1$2',
				'$1 ',
				'$1',
				""
			],
		$input);
	}

	// CSS Minifier => http://ideone.com/Q5USEF + improvement(s)
	private function minifyCSS($input) {
		if (trim($input) === '') return $input;
		return preg_replace(
			[
				// Remove comment(s)
				'#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
				// Remove unused white-space(s)
				'#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
				// Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
				'#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
				// Replace `:0 0 0 0` with `:0`
				'#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
				// Replace `background-position:0` with `background-position:0 0`
				'#(background-position):0(?=[;\}])#si',
				// Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
				'#(?<=[\s:,\-])0+\.(\d+)#s',
				// Minify string value
				'#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
				'#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
				// Minify HEX color code
				'#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
				// Replace `(border|outline):none` with `(border|outline):0`
				'#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
				// Remove empty selector(s)
				'#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
			],
			[
				'$1',
				'$1$2$3$4$5$6$7',
				'$1',
				':0',
				'$1:0 0',
				'.$1',
				'$1$3',
				'$1$2$4$5',
				'$1$2$3',
				'$1:0',
				'$1$2'
			],
		$input);
	}
}
