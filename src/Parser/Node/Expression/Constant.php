<?php
namespace App\Core\PHTML\Parser\Node\Expression;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node\Expression;

class Constant extends Expression
{
	public function __construct($value, int $line)
	{
		parent::__construct([], ['value' => $value], $line);
	}

	public function compile(Compiler $compiler)
	{
		$compiler->repr($this->getAttribute('value'));
	}
}
