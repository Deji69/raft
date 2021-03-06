<?php
namespace Raft\Parser\Node\Expression;

use Raft\Compiler;
use Raft\Parser\Node;
use Raft\Parser\Node\Expression;

abstract class Call extends Expression
{
	protected function compileCallable(Compiler $compiler)
	{
		$callable = $this->getAttribute('callable');
		$compiler->raw($callable)->raw('(');
		$this->compileArguments($compiler);
		$compiler->raw(')');
	}

	protected function compileArguments(Compiler $compiler)
	{
		$first = true;
		if ($this->hasNode('arguments')) {
			$args = $this->getArguments($this->getNode('arguments'));
			foreach ($args as $node) {
				if (!$first) {
					$compiler->raw(', ');
				}
				$compiler->subcompile($node);
				$first = false;
			}
		}
	}

	protected function getArguments(Node $arglist)
	{
		$arguments = [];
		foreach ($arglist as $name => $node) {
			$parameters[$name] = $node;
		}
		return $parameters;
	}
}
