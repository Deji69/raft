<?php
namespace Raft\Parser\Node\Expression;

use Raft\Compiler;
use Raft\Parser\Node;
use Raft\Parser\Node\Call;

class FunctionCall extends Call
{
	public function __construct(string $name, Node $args, int $lineno)
	{
		parent::__construct(['arguments' => $args], ['name' => $name], $lineno);
	}

	public function compile(Compiler $compiler)
	{
		$name = $this->getAttribute('name');
		$this->setAttribute('callable', '$this->'.$name);
		$this->compileCallable($compiler);
	}
}
