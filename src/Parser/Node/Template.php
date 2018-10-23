<?php
namespace Raft\Parser\Node;

use Raft\Compiler;
use Raft\Parser\Node;

class Template extends Node
{
	public function compile(Compiler $compiler)
	{
		$compiler
			->writeHeader()
		;
		parent::compile($compiler);
		$compiler
			->writeFooter()
		;
	}
}
