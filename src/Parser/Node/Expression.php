<?php
namespace App\Core\PHTML\Parser\Node;

use App\Core\PHTML\Parser\Node;

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
