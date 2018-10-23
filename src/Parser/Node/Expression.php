<?php
namespace Raft\Parser\Node;

use Raft\Parser\Node;

/**
 * Abstract class for all nodes that represents an expression.
 */
abstract class Expression extends Node
{
	public function doesAssign()
	{
		return false;
	}
}
