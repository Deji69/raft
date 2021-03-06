<?php
namespace Raft\Parser\Node;

use Raft\Compiler;
use Raft\Parser\Node;
use Raft\Parser\Node\Block;

/**
 * Represents a block call node.
 */
class BlockReference extends Node implements OutputInterface
{
	public function __construct(string $name, Block $block = null, int $lineno, string $tag = null)
	{
		parent::__construct([], ['name' => $name, 'block' => $block], $lineno, $tag);
	}

	public function compile(Compiler $compiler)
	{
		$name = $this->getAttribute('name');
		$block = $this->getAttribute('block');
		$compiler->write("\$this->displayBlock('$name'");

		if ($block !== null) {
			$compiler->raw(', ')->subcompile($block);
		}

		$compiler->raw(");\n");
	}
}
