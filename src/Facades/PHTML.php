<?php
namespace Raft\Facades;

use Illuminate\Support\Facades\Facade;

class PHTML extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return static::$app['view']->getEngineResolver()->resolve('phtml')->getCompiler();
	}
}
