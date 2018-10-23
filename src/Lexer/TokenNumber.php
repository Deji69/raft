<?php
namespace App\Core\PHTML\Lexer;

use App\Core\PHTML\Parser\Token;

trait TokenNumber
{
	public function lexNumber($src, $pos)
	{
		if (preg_match('#[0-9]+(?:\.[0-9]+)?#A', $src, $m, 0, $pos)) {
			return $m[0][1] + strlen($m[0][0]);
		}
		return false;
	}

	public function parseNumber($token)
	{
		$value = (float)$token;
		if (preg_match('#^[0-9]+$#', $token) && $value <= PHP_INT_MAX) {
			$value = (int)$token;
		}
		return new Token('number', $value);
	}
}
