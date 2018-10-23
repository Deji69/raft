<?php
namespace Raft\Lexer;

trait TokenWhiteSpace
{
	public function lexWhiteSpace($src, $pos) {
		$expr = '#\G[\h]+#';
		if (preg_match($expr, $src, $m, PREG_OFFSET_CAPTURE, $pos)) {
			return $m[0][1] + strlen($m[0][0]);
		}
		return false;
	}
}
