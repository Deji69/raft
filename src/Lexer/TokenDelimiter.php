<?php
namespace Raft\Lexer;

trait TokenDelimiter
{
	protected $delimiterTable = [
		'(', ')', '[', ']'
	];

	public function lexDelimiter($src, $pos) {
		if (in_array($src[$pos], $this->delimiterTable)) {
			return $pos + 1;
		}
		return false;
	}
}
