<?php
namespace Raft\Lexer;

trait TokenSeparator
{
	protected $separatorTable = [
		':', ',', '.'
	];

	public function lexSeparator($src, $pos) {
		if (in_array($src[$pos], $this->separatorTable)) {
			return $pos + 1;
		}
		return false;
	}
}
