<?php
namespace Raft\Parser\Node\Expression;

use Raft\Compiler;
use Raft\Parser\Node\Expression;

class Constant extends Expression
{
	public function __construct($value, int $line)
	{
		parent::__construct([], ['value' => $value], $line);
	}

	public function compile(Compiler $compiler)
	{
		$compiler->repr($this->getAttribute('value'));
	}
}
