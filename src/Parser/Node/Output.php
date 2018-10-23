<?php
namespace App\Core\PHTML\Parser\Node;

use App\Core\PHTML\Compiler;
use App\Core\PHTML\Parser\Node;
use App\Core\PHTML\Parser\Node\Expression;
use App\Core\PHTML\Parser\Node\Expression\Variable;

class Output extends Node implements OutputInterface
{
	public function __construct(Expression $expr, int $lineno, string $tag = null)
	{
		parent::__construct(['expr' => $expr], [], $lineno, $tag);
	}

	public function compile(Compiler $compiler)
	{
		$expr = $this->getNode('expr');
		$compiler->output(function (Compiler $compiler) use ($expr) {
			$compiler->subcompile($expr);
			if ($expr instanceof Variable) {
				$compiler->raw(' ?? \'\'');
			}
		});
	}
}
