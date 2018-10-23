<?php
namespace Raft;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\View\Engines\EngineResolver;

class ServiceProvider extends BaseServiceProvider
{
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('view.finder', function ($app) {
			return new TemplateFinder($app['files'], $app['config']['view.paths']);
		});

		// The Compiler engine requires an instance of the CompilerInterface, which in
		// this case will be the FML compiler, so we'll first create the compiler
		// instance to pass into the engine so it can compile the views properly.
		$this->app->singleton('phtml.engine', function () {
			//return new Compiler($this->app['files'], $this->app['config']['view.compiled']);
			return new Engine();
		});

		$this->app['view']->addExtension('phtml', 'PHTML');

		$resolver = $this->app['view.engine.resolver'];
		$resolver->register('PHTML', function () {
			return new ViewEngine($this->app['phtml.engine'], $this->app['files'], $this->app['config']['view.compiled']);
		});
	}
}
