<?php
namespace App\Core\PHTML\Lexer;

trait TokenIdentifier
{
	public function lexIdentifier($src, $pos) {
		if (preg_match('#\G[A-Za-z_][\w\-]*#', $src, $m, 0, $pos)) {
			return $pos + strlen($m[0]);
		}
		return false;
	}
}
