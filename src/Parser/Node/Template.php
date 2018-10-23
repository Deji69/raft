<?php
namespace App\Core\PHTML\Parser\Node;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node;

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
