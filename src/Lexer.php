<?php
namespace Raft;

use Raft\Token;
use Raft\Engine;
use Raft\TokenStream;
use Raft\Exception\SyntaxError;
use InvalidArgumentException;

/**
 * Takes template code and converts it into a TokenStream.
 */
class Lexer
{
	// order affects lexer priority
	use Lexer\TokenWhiteSpace,
		Lexer\TokenNewLine,
		Lexer\TokenNumber,
		Lexer\TokenOperator,
		Lexer\TokenIdentifier,
		Lexer\TokenString,
		Lexer\TokenSeparator,
		Lexer\TokenDelimiter;

	/**
	 * The source file currently being lexed.
	 *
	 * @var Source
	 */
	protected $source;

	/**
	 * @var Token[]
	 */
	protected $tokens = [];

	/**
	 * @var string[]
	 */
	protected $tokenTypes = [];

	/**
	 * @var array
	 */
	protected $delimiters = ['{{', '}}'];

	/**
	 * @param Engine $engine
	 */
	public function __construct(Engine $engine)
	{
		$uses = class_uses(static::class);

		foreach ($uses as $trait) {
			$arr = explode('\\', $trait);
			$type = end($arr);
			if (strStartsWith($type, 'Token')) {
				$this->tokenTypes[] = substr($type, 5);
			}
		}
	}

	/**
	 * Add a token to the output stream.
	 *
	 * @param string 	$type
	 * @param int 		$cursor
	 * @param int 		$length
	 */
	public function addToken(string $type, int $cursor, int $length)
	{
		$this->tokens[] = new Token($type, $this->source->getToken($cursor, $length), $cursor);
	}

	/**
	 * Add a token with custom data to the output stream.
	 *
	 * @param string 	$type
	 * @param mixed 	$data
	 * @param int 		$cursor
	 * @param int 		$length
	 */
	public function addRawToken(string $type, $data, int $cursor, int $length)
	{
		$this->tokens[] = new Token($type, $data, $cursor);
	}

	/**
	 * Lex the given template contents.
	 *
	 * @param  Source|string  $value
	 * @return TokenStream
	 */
	public function tokenize($source): TokenStream
	{
		if (is_string($source)) {
			$source = new Source($source, 'noname');
		} elseif (!$source instanceof Source) {
			throw new InvalidArgumentException('Expected string or instance of '.Source::class);
		}

		Engine::getActiveEngine()->setSource($source);

		$this->source = $source;
		$this->tokens = [];

		$code = $this->source->getCode();
		$npos = strlen($code);
		$str = '';
		$offset = 0;
		$phpJustOpened = false;

		$zendTokens = token_get_all($code);

		for ($i = 0; $i < count($zendTokens); ++$i) {
			$token = $zendTokens[$i];

			// raw PHP blocks
			if ($token[0] == T_OPEN_TAG) {
				$phpTokens = [$token];
				$phpOpen = true;
				$phpStartCursor = $this->source->getOffsetForLine($token[2]);
				$phpLength = strlen($token[1]);

				while (++$i < count($zendTokens)) {
					$token = $zendTokens[$i];
					$phpTokens[] = $token;
					$phpLength += strlen($token[1]);

					if ($token[0] == T_CLOSE_TAG) {
						$phpOpen = false;
						break;
					}
				}

				$this->addRawToken(Token::PHP, $phpTokens, $phpStartCursor, $phpLength);
				$offset += $phpLength;
				continue;
			}

			$this->tokenizeString($token[1], $offset);
			$offset += strlen($token[1]);
		}

		$this->addToken(Token::EOF, $npos, 0);
		return new TokenStream($this->tokens, $this->source);
	}

	/**
	 * Tokenizes template contents.
	 *
	 * @param  string 	$str
	 * @param  int 		$offset
	 * @return void
	 */
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
						$this->addToken(Token::RAW, $pos + $offset, $length);
					}

					$this->addToken(Token::BEGIN, $offset + $delimStart, $delimStartLength);
					$this->lex($expression, $offset + $delimStart + $delimStartLength);
					$this->addToken(Token::END, $offset + $delimEnd, $delimEndLength);
					$delimEnd += $delimEndLength;
					$pos = $delimEnd;
					$nextPos = $pos;
					continue;
				}
			}

			$length = $nextPos - $pos;
			if ($length) {
				$this->addToken(Token::RAW, $pos + $offset, $length);
			}
		}
	}

	/**
	 * Lexes a Raft block.
	 *
	 * @param string $expr
	 * @return void
	 */
	public function lex(string $expr, int $offset = 0)
	{
		$endPos = 0;
		$len = strlen($expr);
		if (!$len) {
			return;
		}
		do {
			$pos = $endPos;
			if ($pos > $len) {
				break;
			}
			$unknown = true;
			foreach ($this->tokenTypes as $type) {
				$endPos = $this->{"lex{$type}"}($expr, $pos);
				if ($endPos > $pos) {
					$type = strtolower($type);
					if ($type == 'whitespace') {
						$unknown = false;
						break;
					}
					if ($type == 'string') {
						$this->addToken($type, $pos + $offset + 1, $endPos - $pos - 2);
					} else {
						$this->addToken($type, $pos + $offset, $endPos - $pos);
					}
					$unknown = false;
					break;
				}
			}
			if ($unknown) {
				throw new SyntaxError('Unexpected \''.substr($expr, $pos).'\'', $pos, $this->source);
			}
		} while($endPos > $pos && $endPos < $len);
	}
}
