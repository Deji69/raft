<?php
namespace Raft\Parser\Node\Expression;

use Raft\Compiler;
use Raft\Parser\Node\Expression;

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
