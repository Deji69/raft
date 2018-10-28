<?php
namespace Raft\Lexer;

trait TokenNumber
{
	public function lexNumber($src, $pos)
	{
		if (preg_match('#[+\-]?(?:0|[1-9]\d*)(?:\.\d*)?(?:[eE][+\-]?\d+)?#A', $src, $m, 0, $pos)) {
			return $pos + strlen($m[0]);
		}
		return false;
	}
}
