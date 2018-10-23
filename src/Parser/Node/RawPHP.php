<?php
namespace Raft\Parser\Node;

use Raft\Compiler;
use Raft\Parser\Node;

class RawPHP extends Raw
{
	public function compile(Compiler $compiler)
	{
		$compiler->raw($this->getAttribute('data')."\n");
	}
}
