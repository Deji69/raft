<?php
namespace App\Core\PHTML\Parser\Node\Expression;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node;
use App\Core\PHTML\Parser\Node\Call;

class FunctionCall extends Call
{
	public function __construct(string $name, Node $args, int $lineno)
	{
		parent::__construct(['arguments' => $args], ['name' => $name], $lineno);
	}

	public function compile(Compiler $compiler)
	{
		$name = $this->getAttribute('name');
		$this->setAttribute('callable', '$this->'.$name);
		$this->compileCallable($compiler);
	}
}
