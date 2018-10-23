<?php
namespace Raft\Parser\Node;

use Raft\Compiler;
use Raft\Parser\Node;

class Raw extends Node
{
	public function __construct(string $text, int $offset)
	{
		parent::__construct([], $text, $offset);
	}

	public function compile(Compiler $compiler)
	{
		$compiler->output($this->getAttribute('data'));
	}
}
