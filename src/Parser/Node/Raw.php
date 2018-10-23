<?php
namespace App\Core\PHTML\Parser\Node;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node;

class Raw extends Node
{
	public function __construct(string $text, int $offset)
	{
		parent::__construct([], $text, $offset);
	}

	public function compile(Compiler $compiler)
	{
		$compiler->output($this->getAttribute('data'));
	}
}
