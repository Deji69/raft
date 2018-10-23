<?php
namespace Raft\Parser\Node;

use Raft\Compiler;
use Raft\Parser\Node;

/**
 * Represents a block node.
 */
class Block extends Node
{
	public function __construct(string $name, Node $body, int $lineno, string $tag = null)
	{
		parent::__construct(['body' => $body], ['name' => $name], $lineno, $tag);
	}

	public function compile(Compiler $compiler)
	{
		$name = $this->getAttribute('name');
		$compiler
			->raw('$this->block(')->string($name)->raw(", function () {\n")
			->indent()
			->subcompile($this->getNode('body'))
			->outdent()
			->write("})")
		;
	}
}
