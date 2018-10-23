<?php
namespace App\Core\PHTML\Parser\Node\Expression\Binary;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node\Expression\BinaryExpression;

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
