<?php
namespace Raft;

use Raft\Source;

/**
 * Represents a PHTML token
 */
class Token
{
	/**
	 * Built-in token types
	 */
	const EOF			= 'eof';		// indicates the end of a template
	const RAW			= 'raw';		// used for unparsed template content
	const PHP			= 'php';		// used for blocks of PHP code
	const WHITESPACE	= 'whitespace';	// indicates horizontal whitespace blocks
	const NEWLINE		= 'newline';	// indicates blocks of newlines and lines with only whitespace
	const IDENTIFIER	= 'identifier';	// used for eligible names of vars, funcs, etc.
	const TEXT			= 'text';		// used for text strings like 'this' and "this"
	const NUMBER		= 'number';
	const DELIMITER		= 'delimiter';
	const OPERATOR		= 'operator';
	const SEPARATOR		= 'separator';

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string|null
	 */
	protected $value;

	/**
	 * Offset position of the source code - null indicates a spoof token
	 *
	 * @var int|null
	 */
	protected $offset;

	/**
	 * @param string	$type
	 * @param string	$value
	 * @param int		$offset
	 */
	public function __construct(string $type, string $value = null, int $offset = null)
	{
		$this->type = $type;
		$this->value = $value;
		$this->offset = $offset;
	}

	/**
	 * Returns a string representation of the token.
	 *
	 * @return string A string representation of the token
	 */
	public function __toString()
	{
		if ($line = $this->getLine()) {
			$column = $this->getColumn();
			return sprintf('%3d,%3d %s %s', $line, $column ?? 0, strtoupper($this->type), $this->value);
		}
		return sprintf('%3d %s %s', $this->offset, strtoupper($this->type), $this->source);
	}

	/**
	 * Gets the type of this token.
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * Gets the value of this token.
	 *
	 * @return string
	 */
	public function getValue(): ?string
	{
		return $this->value;
	}

	/**
	 * Get the offset of this token.
	 *
	 * @return int
	 */
	public function getOffset(): int
	{
		return $this->offset;
	}

	/**
	 * Get the line number of this token.
	 *
	 * @return int|null
	 */
	public function getLine(): ?int
	{
		$engine = Engine::getActiveEngine();
		if ($engine) {
			if ($source = $engine->getSource()) {
				return $source->getLineForOffset($this->offset);
			}
		}
		return null;
	}

	/**
	 * Get the column number of this token.
	 *
	 * @return int|null
	 */
	public function getColumn(): ?int
	{
		$engine = Engine::getActiveEngine();
		if ($engine) {
			if ($source = $engine->getSource()) {
				return $source->getColumnForOffset($this->offset);
			}
		}
		return null;
	}

	/**
	 * Tests the current token for a type and/or a value.
	 *
	 * @param  string|string[]	$type	The type to test
	 * @param  string|null		$value	The token value
	 * @return bool
	 */
	public function is($type, string $value = null): bool
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
