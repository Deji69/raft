<?php
namespace Raft\Parser\Node;

use Raft\Compiler;
use Raft\Parser\Node;
use Raft\Parser\Node\Expression;
use Raft\Parser\Node\Expression\Variable;

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
