<?php
namespace Raft;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Raft\Lexer\Token;
use Raft\Parser\Parser;
use Illuminate\Filesystem\Filesystem;
use Raft\Exceptions\SyntaxError;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\Compilers\Compiler as BaseCompiler;

class Lexer
{
	// order affects lexer priority
	use Lexer\TokenWhiteSpace,
		Lexer\TokenNewLine,
		Lexer\TokenIdentifier,
		Lexer\TokenString,
		Lexer\TokenOperator,
		Lexer\TokenSeparator,
		Lexer\TokenDelimiter;

	/**
	 * The ssource file currently being lexed.
	 *
	 * @var Source
	 */
	protected $source;

	/**
	 * The template parser
	 *
	 * @var \App\FML\Parser\Parser
	 */
	protected $parser;

	/**
	 * The compiled template output
	 *
	 * @var string
	 */
	protected $output;

	/**
	 * Ouput tokens
	 *
	 * @var Token[]
	 */
	protected $tokens = [];

	/**
	 * Delimiters
	 *
	 * @var array
	 */
	protected $delimiters = ['{{', '}}'];

	public function __construct(Engine $engine)
	{
		$uses = class_uses(static::class);

		foreach ($uses as $trait) {
			$arr = explode('\\', $trait);
			$type = end($arr);
			if (Str::startsWith($type, 'Token')) {
				$this->tokenTypes[] = substr($type, 5);
			}
		}
	}

	public function addToken(string $type, int $cursor, int $length)
	{
		$this->tokens[] = new Token($this->source, $type, $cursor, $length);
	}

	public function addRawToken(string $type, int $cursor, int $length, string $code)
	{
		$this->tokens[] = new Token($this->source, $type, $cursor, $length, $code);
	}

	/**
	 * Compile the given PHTML template contents.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function tokenize($source)
	{
		$this->source = $source;
		$this->tokens = [];

		$code = $this->source->getCode();
		$npos = strlen($code);
		$str = '';
		$phpOpen = false;
		$phpLine = 0;
		$offset = 0;
		$phpJustOpened = false;

		foreach (token_get_all($code) as $token) {
			if ($token[0] == T_OPEN_TAG) {
				$phpOpen = true;
				//$offset = $this->source->getOffsetForLine($token[2]);
			} else if ($token[0] == T_CLOSE_TAG) {
				$this->addRawToken('php', $offset, strlen($str), $str);
				$str = '';
				$phpOpen = false;
				$phpLine = $token[2];
				$offset += strlen($token[1]);
				continue;
			}
			if ($phpOpen) {
				if (is_string($token)) {
					$str .= $token;
					$offset += strlen($token);
				} else {
					if ($token[0] != T_OPEN_TAG) {
						if ($token[0] == T_VARIABLE && $token[1] !== '$this') {
							$str .= '$this->vars[\''.substr($token[1], 1).'\']';
						} else {
							$str .= $token[1];
						}
					} else {
						$phpJustOpened = true;
					}
					$offset += strlen($token[1]);
					$phpLine = $token[2];
				}
			} else {
				$this->tokenizeString($token[1], $offset);
				$offset += strlen($token[1]);
			}
		}

		$this->addToken('eof', $npos, 0);
		return new TokenStream($this->tokens);
	}

	public function tokenizeString(string $str, int $offset = 0)
	{
		$endPos = strlen($str);
		$nextPos = $endPos;
		$delimStartLength = strlen($this->delimiters[0]);
		$delimEndLength = strlen($this->delimiters[1]);

		for ($pos = 0; $pos < $endPos; $pos = $nextPos, $nextPos = $endPos) {
			$delimStart = strpos($str, $this->delimiters[0], $pos);
			if ($delimStart !== false) {
				$delimEnd = strpos($str, $this->delimiters[1], $pos + $delimStartLength);
				if ($delimEnd !== false) {
					$expression = substr($str, $delimStart + $delimStartLength, $delimEnd - $delimStart - $delimStartLength);
					$length = $delimStart - $pos;
					if ($length) {
						$this->addToken('raw', $pos + $offset, $length);
					}
					$this->lex($expression, $offset + $delimStart + $delimStartLength);
					$delimEnd += $delimEndLength;
					$pos = $delimEnd;
					/*if (preg_match('#\G\s*#', $str, $m, PREG_OFFSET_CAPTURE, $pos)) {
						$pos += strlen($m[0][0]);
					}
					if ($str[$pos] == '\r' || $str[$pos] == '\n') {
						if ($str[$pos] == '\r') {
							$pos += 2;
						} else {
							$pos += 1;
						}
						if ($str[$pos] == '\t') {
							$pos += 1;
						}
					}*/
					$nextPos = $pos;
					continue;
				}
			}

			$length = $nextPos - $pos;
			if ($length) {
				$this->addToken('raw', $pos + $offset, $length);
			}
		}
	}

	/**
	 * Lexes the given PHTML expression
	 *
	 * @param string $expr
	 * @return void
	 */
	public function lex(string $expr, int $offset = 0)
	{
		$endPos = 0;
		$len = strlen($expr);
		do {
			$pos = $endPos;
			if ($pos > $len) {
				break;
			}
			foreach ($this->tokenTypes as $type) {
				$endPos = $this->{"lex{$type}"}($expr, $pos);
				if ($endPos > $pos) {
					$type = strtolower($type);
					if ($type == 'whitespace') {
						break;
					}
					if ($type == 'string') {
						$this->addToken($type, $pos + $offset + 1, $endPos - $pos - 2);
					} else {
						$this->addToken($type, $pos + $offset, $endPos - $pos);
					}
					break;
				}
			}
		} while($endPos > $pos && $endPos < $len);
	}

	private static function debugGetTokenConstant($id) {
		static $tokenConstants = [];
		if (empty($tokenConstants)) {
			$tokenConstants = array_filter(
				get_defined_constants(),
				function ($v, $k) {
					return substr($k, 0, 2) === 'T_';
				},
				ARRAY_FILTER_USE_BOTH
			);
			$tokenConstants = array_flip($tokenConstants);
		}
		return $tokenConstants[$id] ?? null;
	}
}
