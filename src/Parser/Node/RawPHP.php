<?php
namespace App\Core\PHTML\Parser\Node;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node;

class RawPHP extends Raw
{
	public function compile(Compiler $compiler)
	{
		$compiler->raw($this->getAttribute('data')."\n");
	}
}
