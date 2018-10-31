<?php
namespace Raft;

use Countable;
use Raft\Token;
use Raft\Source;
use Raft\Exception\SyntaxError;

/**
 * Represents a PHTML token
 */
class TokenStream implements Countable
{
	/**
	 * The current token in the stream
	 *
	 * @var Token
	 */
	public $current;

	/**
	 * The array of tokens
	 *
	 * @var array
	 */
	protected $tokens;

	/**
	 * Code Source object
	 *
	 * @var Source
	 */
	protected $source;

	/**
	 * Current position in the token stream.
	 *
	 * @var int
	 */
	protected $position = 0;

	public function __construct(array $tokens, Source $source = null)
	{
		$this->tokens = $tokens;
		$this->source = $source ?? new Source('', '');
		$this->current = $tokens[0];
	}

	/**
	 * Returns a string representation of the token stream.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return implode("\n", $this->tokens);
	}

	/**
	 * Checks if end of stream was reached.
	 *
	 * @return bool
	 */
	public function isEOF(): bool
	{
		return $this->current->is('eof');
	}

	/**
	 * Skip any number of whitespace tokens.
	 *
	 * @return void
	 */
	public function skipWhitespace($andNewlines = false)
	{
		while ($this->current->is([Token::WHITESPACE, Token::NEWLINE])) {
			$this->next();
		}
	}

	/**
	 * Sets the pointer to the next token and returns the current one.
	 *
	 * @param  bool $skipWhitespace
	 *
	 * @return Token
	 *
	 * @throws InternalErrorException If there is no more token
	 */
	public function next(bool $skipWhitespace = true): Token
	{
		$token = $this->current;
		if (!isset($this->tokens[++$this->position])) {
			throw new SyntaxError('Unexpected end of template.', $this->tokens[$this->position - 1]->getLine(), $this->source);
		}
		$this->current = $this->tokens[$this->position];
		if ($skipWhitespace) {
			$this->skipWhitespace();
		}
		return $token;
	}

	/**
	 * Returns an upcoming token.
	 *
	 * @param  int $number
	 *
	 * @return Token
	 */
	public function peek($number = 1)
	{
		if (count($this->tokens) <= $this->position + $number) {
			return null;
		}
		return $this->tokens[$this->position + $number];
	}

	/**
	 * Asserts a token.
	 *
	 * @param string|string[] 	$type 		The type to test
	 * @param string|null 		$value 		The token value
	 * @param string|null 		$message 	The syntax error message
	 */
	public function expect($type, string $value = null, string $message = null): Token
	{
		$token = $this->current;
		if (!$token->is($type, $value)) {
			$line = $token->getLine();
			throw new SyntaxError(sprintf('%sUnexpected token "%s" of value "%s" ("%s" expected%s).',
				$message ? $message.'. ' : '',
				$token->type,
				$token->source,
				$type,
				$value ? sprintf(' with value "%s"', $value) : ''),
				$line,
				$this->source
			);
		}
		$this->next();
		return $token;
	}

	/**
	 * @return Source
	 */
	public function getSource(): Source
	{
		return $this->source;
	}

	/**
	 * @return int
	 */
	public function count(): int
	{
		return count($this->tokens);
	}
}
