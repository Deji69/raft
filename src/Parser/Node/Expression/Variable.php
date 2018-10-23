<?php
namespace App\Core\PHTML\Parser\Node\Expression;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node\Expression;

class Variable extends Expression
{
	public function __construct($value, int $line)
	{
		parent::__construct([], ['name' => $value], $line);
	}

	public function compile(Compiler $compiler)
	{
		$name = $this->getAttribute('name');
		$compiler
			->variable($name)
		;
	}
}
