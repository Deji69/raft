<?php
namespace Raft\Lexer;

trait TokenIdentifier
{
	public function lexIdentifier($src, $pos) {
		if (preg_match('#[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*#A', $src, $m, 0, $pos)) {
			return $pos + strlen($m[0]);
		}
		return false;
	}
}
