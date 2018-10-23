<?php
namespace App\Core\PHTML;

use InvalidArgumentException;
use Illuminate\Support\Str;
use Illuminate\View\FileViewFinder;

class TemplateFinder extends FileViewFinder
{
	protected $lastTemplateName;

	public function getLastTemplateName()
	{
		return $this->lastTemplateName;
	}

	/**
	 * Find the given view in the list of paths.
	 *
	 * @param  string  $name
	 * @param  array   $paths
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function findInPaths($name, $paths)
	{
		$this->lastTemplateName = $name;

		if (!isset($paths['views'])) {
			return parent::findInPaths($name, $paths);
		}
		$layout = false;
		if (Str::startsWith($name, 'layout\\') && isset($paths['layouts'])) {
			$paths = $paths['layouts'];
			$name = substr($name, 7);
			$layout = true;
		} else {
			$paths = $paths['views'];
		}
		foreach ((array) $paths as $path) {
			foreach ($this->getPossibleViewFiles($name) as $file) {
				if ($this->files->exists($viewPath = $path.'/'.$file)) {
					return $viewPath;
				}
			}
		}

		throw new InvalidArgumentException(($layout ? 'Layout [' : 'View [').$name.'] not found.');
	}
}
