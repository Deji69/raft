<?php
namespace Raft\Parser\Node\Expression\Binary;

use Raft\Compiler;
use Raft\Parser\Node;
use Raft\Parser\Node\Expression\BinaryExpression;

class NullCoalesce extends BinaryExpression
{
	public function operator(Compiler $compiler)
	{
		return $compiler->raw('??');
	}
}
