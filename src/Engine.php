<?php
namespace Raft;

use Exception;
use Throwable;
use ErrorException;
use Raft\Parser;
use Raft\Source;
use Raft\Parser\Node;
use Illuminate\View\Compilers\CompilerInterface;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Engine
{
	/**
	 * Active engine instance
	 *
	 * @var Engine
	 */
	static protected $engine;

	/**
	 * @var Source
	 */
	protected $source;

	/**
	 * @var Lexer
	 */
	protected $lexer;

	/**
	 * @var Parser
	 */
	protected $parser;

	/**
	 * @var CompilerInterface
	 */
	protected $compiler;

	/**
	 * @var Environment
	 */
	protected $environment;

	/**
	 * Create a new Raft engine instance.
	 */
	public function __construct()
	{
		if (!isset(static::$engine)) {
			static::setActiveEngine($this);
		}
	}

	public function __destruct()
	{
		if (static::$engine === $this) {
			static::$engine = null;
		}
	}

	/**
	 * Sets the engine currently running tasks.
	 *
	 * @param Engine $engine
	 */
	public static function setActiveEngine(Engine $engine)
	{
		static::$engine = $engine;
	}

	/**
	 * Gets the engine currently running tasks.
	 *
	 * @param Engine|null $engine
	 */
	public static function getActiveEngine(): ?Engine
	{
		if (static::$engine === null) {
			new static;
		}
		return static::$engine;
	}

	/**
	 * Get the environment instance.
	 *
	 * @return Environment
	 */
	public function getEnvironment(): Environment
	{
		if (!isset($this->environment)) {
			$this->environment = new Environment($this);
		}
		return $this->environment;
	}

	/**
	 * Set the environment instance.
	 *
	 * @param Environment
	 */
	public function setEnvironment(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * Gets source object currently being worked on
	 *
	 * @return Source
	 */
	public function getSource(): Source
	{
		return $this->source;
	}

	/**
	 * Set the source object currently being worked on
	 *
	 * @param Source
	 */
	public function setSource(Source $source)
	{
		$this->source = $source;
	}

	/**
	 * Get the lexer instance.
	 *
	 * @return Lexer
	 */
	public function getLexer()
	{
		if (!isset($this->lexer)) {
			$this->lexer = new Lexer($this);
		}
		return $this->lexer;
	}

	/**
	 * Set the lexer instance.
	 *
	 * @param Lexer
	 */
	public function setLexer(Lexer $lexer)
	{
		$this->lexer = $lexer;
	}

	/**
	 * Get the parser instance.
	 *
	 * @return Parser
	 */
	public function getParser()
	{
		if (!isset($this->parser)) {
			$this->parser = new Parser($this);
		}
		return $this->parser;
	}

	/**
	 * Set the parser instance.
	 *
	 * @param Parser
	 */
	public function setParser(Parser $parser)
	{
		$this->parser = $parser;
	}

	/**
	 * Get the compiler instance.
	 *
	 * @return Compiler
	 */
	public function getCompiler()
	{
		if (!isset($this->compiler)) {
			$this->compiler = new Compiler($this);
		}
		return $this->compiler;
	}

	/**
	 * Set the compiler instance.
	 *
	 * @param Compiler
	 */
	public function setCompiler(Compiler $compiler)
	{
		$this->compiler = $compiler;
	}

	/**
	 * Tokenizes source code into a token stream.
	 *
	 * @param  Source  $source
	 *
	 * @return TokenStream
	 *
	 * @throws Exception\SyntaxError When the code is syntactically wrong
	 */
	public function tokenize(Source $source): TokenStream
	{
		$this->source = $source;
		return $this->getLexer()->tokenize($source);
	}

	/**
	 * Parses a token stream into a node tree.
	 *
	 * @param  Source  $source
	 *
	 * @return Parser\Node
	 *
	 * @throws Exception\SyntaxError When the code is syntactically wrong
	 */
	public function parse(TokenStream $tokenStream): Node
	{
		$this->source = $tokens->getSource();
		return $this->getParser()->parse($tokenStream);
	}

	/**
	 * Compiles a node into PHP code.
	 *
	 * @param  Node  $node
	 *
	 * @return string 	The compiled PHP code
	 */
	public function compile(Node $node): string
	{
		return $this->getCompiler()->compile($node)->getOutput();
	}

	/**
	 * Check whether the given template file needs recompiling
	 *
	 * @param  string  $path
	 *
	 * @return bool
	 */
	public function isExpired(string $path): bool
	{
		return true;
	}
}
