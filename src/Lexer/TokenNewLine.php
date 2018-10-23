<?php
namespace Raft\Lexer;

trait TokenNewLine
{
	public function lexNewLine($src, $pos)
	{
		$expr = '#[\n]+#A';
		if (preg_match($expr, $src, $m, PREG_OFFSET_CAPTURE, $pos)) {
			return $m[0][1] + strlen($m[0][0]);
		}
		return false;
	}
}
