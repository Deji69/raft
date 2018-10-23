<?php
namespace App\Core\PHTML\Parser\Node\Expression\Binary;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node;
use App\Core\PHTML\Parser\Node\Expression\BinaryExpression;

class NullCoalesce extends BinaryExpression
{
	public function operator(Compiler $compiler)
	{
		return $compiler->raw('??');
	}
}
