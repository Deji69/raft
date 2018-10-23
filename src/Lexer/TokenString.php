<?php
namespace Raft\Lexer;

trait TokenString
{
	protected static $stringDelimiters = ['\'', '\''];

	public function lexString($src, $pos) {
		if ($src[$pos] == static::$stringDelimiters[0]) {
			$startPos = $pos;

			// skip the delimiter char and find the first matching delimiter after it
			while (($pos = mb_strpos($src, static::$stringDelimiters[1], ++$pos, 'UTF-8')) !== false) {
				$rpos = $pos - 1;
				// if the last character pos is where we started, we have an "" (empty string situation)
				if ($rpos == $startPos) {
					$slashCount = 0;
				} else {
					// count the number of consecutive \ (backslash) chars preceeding the ending delimiter
					while ($src[$rpos] == '\\') {
						--$rpos;
					}
					$slashCount = $pos - $rpos - 1;
				}
				// if the number of backslash chars is 0 or divisible by 2, $pos is the end delimiter
				if ($slashCount % 2 == 0) {
					return $pos + 1;
				}
			}
		}
		return false;
	}
}
