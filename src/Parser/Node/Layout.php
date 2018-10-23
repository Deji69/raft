<?php
namespace App\Core\PHTML\Parser\Node;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node;

class Layout extends Template
{
	public function compile(Compiler $compiler)
	{
		parent::compile($compiler);
	}
}
