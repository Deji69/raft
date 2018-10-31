<?php
namespace Raft;

use Illuminate\Contracts\View\Engine;
use Illuminate\Contracts\View\Factory as ViewFactory;

class Environment
{
	protected $vars = [];
	protected $blocks = [];
	protected $symbols = [];
	protected $library = [];
	protected $output = '';

	public function __construct()
	{
		$this->library['url'] = [];
		$this->library['url']['to'] = function (string $path = null, $parameters = [], bool $secure = null)
			{
				return url($path, $parameters, $secure);
			};
		$this->library['url']['current'] = function (string $path = null, $parameters = [], bool $secure = null)
			{
				return url()->current();
			};
	}

	public function run($__path, $__data)
	{
		if (isset($__data['__blocks'])) {
			$this->blocks = $__data['__blocks'];
			$__data['__blocks'] = array_keys($__data['__blocks']);
		}

		$this->output = '';
		$this->vars = array_merge($this->vars, $__data);

		include $__path;

		if (isset($template)) {
			$template();
		}

		echo $this->output;
	}

	public function setVar($name, $value = '')
	{
		$this->vars[$name] = $value;
	}

	public function getVar($name, $default = '')
	{
		return $this->vars[$name] ?? $default;
	}

	public function output($value)
	{
		$this->output .= $value;
	}

	public function block(string $name, callable $fn)
	{
		if (!isset($this->blocks[$name])) {
			$this->blocks[$name] = $fn();
		}
		return $this->blocks[$name] ?? '';
	}

	public function displayBlock(string $name, string $default = '')
	{
		if (isset($this->blocks[$name])) {
			$this->output .= $this->blocks[$name];
		} else {
			$this->output .= $default;
		}
	}
}
