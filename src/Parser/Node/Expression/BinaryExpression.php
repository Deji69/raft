<?php
namespace App\Core\PHTML\Parser\Node\Expression;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node;
use App\Core\PHTML\Parser\Node\Expression;

abstract class BinaryExpression extends Expression
{
	public function __construct(Node $lhs, Node $rhs, int $lineno)
	{
		parent::__construct(['lhs' => $lhs, 'rhs' => $rhs], [], $lineno);
	}

	public function compile(Compiler $compiler)
	{
		$compiler
			->subcompile($this->getNode('lhs'))
			->raw(' ')
		;
		$this->operator($compiler);
		$compiler
			->raw(' ')
			->subcompile($this->getNode('rhs'))
		;
		if ($this->doesAssign()) {
			$compiler->raw(";\n");
		}
	}

	abstract public function operator(Compiler $compiler);
}
