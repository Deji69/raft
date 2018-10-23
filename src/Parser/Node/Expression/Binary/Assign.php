<?php
namespace Raft\Parser\Node\Expression\Binary;

use Raft\Compiler;
use Raft\Parser\Node\Expression\BinaryExpression;

class Assign extends BinaryExpression
{
	public function operator(Compiler $compiler)
	{
		return $compiler->raw('=');
	}

	public function doesAssign()
	{
		return true;
	}
}
