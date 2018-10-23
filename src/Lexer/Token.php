<?php
namespace App\Core\PHTML\Lexer;

use App\Core\PHTML\Source;

/**
 * Represents a PHTML token
 */
class Token
{
	/**
	 * Type of token
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Source code represented by token
	 *
	 * @var string
	 */
	protected $code;

	/**
	 * Cursor position of the source code
	 *
	 * @var int
	 */
	protected $cursor;

	/**
	 * Number of characters (not bytes) of source code represented by the token
	 *
	 * @var int
	 */
	protected $length;

	/**
	 * Line number of the token
	 *
	 * @var int
	 */
	protected $line;

	/**
	 * Line column of the token
	 *
	 * @var int
	 */
	protected $column;

	/**
	 * Full source code object
	 *
	 * @var Source
	 */
	protected $source;

	public function __construct(Source $source, string $type, int $cursor, int $length, string $code = null)
	{
		$this->source = $source;
		$this->type = $type;
		$this->cursor = $cursor;
		$this->length = $length;
		$this->line = $source->getLineForOffset($cursor);
		$this->column = $cursor - $source->getOffsetForLine($this->line) + 1;
		$this->code = $code === null ? $source->getToken($cursor, $length) : $code;
	}

	/**
	 * Returns a string representation of the token.
	 *
	 * @return string A string representation of the token
	 */
	public function __toString()
	{
		return sprintf('%3d %s %s', $this->cursor, strtoupper($this->type), $this->source);
	}

	public function getSource(): Source
	{
		return $this->source;
	}

	public function get(): string
	{
		return $this->code;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getCursor(): int
	{
		return $this->cursor;
	}

	public function getLength(): int
	{
		return $this->length;
	}

	public function getLine(): int
	{
		return $this->line;
	}

	public function getColumn(): int
	{
		return $this->column;
	}

	/**
	 * Tests the current token for a type and/or a value.
	 *
	 * @param string|string[] 	$type  The type to test
	 * @param string|null 		$value The token value
	 *
	 * @return bool
	 */
	public function is($type, string $value = null)
	{
		if (is_array($type)) {
			foreach ($type as $realtype) {
				if ($this->is((string)$realtype)) {
					return true;
				}
			}
			return false;
		}
		return $this->type === $type && ($value === null || $this->code == $value);
	}
}
