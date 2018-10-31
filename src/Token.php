<?php
namespace Raft;

use Raft\Source;
use InvalidArgumentException;

/**
 * Represents a PHTML token
 */
class Token
{
	/**
	 * Built-in token types
	 */
	const EOF			= 0;	// indicates the end of a template
	const RAW			= 1;	// used for unparsed template content
	const BEGIN			= 2;	// indicates the beginning of a tag e.g. {{
	const END			= 3;	// indicates the end of a tag e.g. }}
	const WHITESPACE	= 4;	// indicates horizontal whitespace blocks
	const NEWLINE		= 5;	// indicates blocks of newlines and lines with only whitespace
	const IDENTIFIER	= 6;	// used for eligible names of vars, funcs, etc.
	const STRING		= 7;	// used for text strings like 'this' and "this"
	const NUMBER		= 8;
	const DELIMITER		= 9;
	const OPERATOR		= 10;
	const SEPARATOR		= 11;
	const EXTRA			= 100;	// pseudo type for the starting ID of user-defined token types

	static protected $typeNames = [
		self::EOF			=> 'eof',
		self::RAW			=> 'raw',
		self::BEGIN			=> 'tag start',
		self::END			=> 'tag end',
		self::WHITESPACE	=> 'whitespace',
		self::NEWLINE		=> 'new line',
		self::IDENTIFIER	=> 'identifier',
		self::STRING		=> 'string',
		self::NUMBER		=> 'number',
		self::DELIMITER		=> 'delimiter',
		self::OPERATOR		=> 'operator',
		self::SEPARATOR		=> 'separator',
	];

	static protected $typeFullNames = [
		self::EOF			=> 'end of file',
		self::RAW			=> 'raw template data',
		self::BEGIN			=> 'start of tag',
		self::END			=> 'end of tag',
	];

	/**
	 * @var int
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
	 * @param int		$type
	 * @param mixed		$value
	 * @param int		$offset
	 */
	public function __construct(int $type, string $value = null, int $offset = null)
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
		$typeName = strtoupper(self::getTypeFullName($this->type));
		if ($line = $this->getLine()) {
			$column = $this->getColumn();
			if (!is_string($this->value)) {
				return sprintf('%3d,%3d %s', $line, $column ?? 0, $typeName);
			}
			return sprintf('%3d,%3d %s %s', $line, $column ?? 0, $typeName, $this->value);
		}
		return sprintf('%3d %s %s', $this->offset, $typeName, $this->value);
	}

	/**
	 * Gets the type of this token.
	 *
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * Gets the value of this token.
	 *
	 * @return string|null
	 */
	public function getValue(): ?string
	{
		return $this->value;
	}

	/**
	 * Get the offset of this token.
	 *
	 * @return int|null
	 */
	public function getOffset(): ?int
	{
		return $this->offset;
	}

	/**
	 * Get the length of this token.
	 *
	 * @return int
	 */
	public function getLength(): int
	{
		return $this->value ? strlen($this->value) : 0;
	}

	/**
	 * Get the offset of the end of this token.
	 *
	 * @return int
	 */
	public function getOffsetEnd(): ?int
	{
		return $this->getOffset() + $this->getLength();
	}

	/**
	 * Get the line number of this token.
	 *
	 * @return int|null
	 */
	public function getLine(): ?int
	{
		$engine = Engine::getActiveEngine();
		if ($engine && isset($this->offset)) {
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
		if ($engine && isset($this->offset)) {
			if ($source = $engine->getSource()) {
				return $source->getColumnForOffset($this->offset);
			}
		}
		return null;
	}

	/**
	 * Tests the current token for a type and/or a value.
	 *
	 * @param  int|int[]		$type	The type to test
	 * @param  string|null		$value	The token value
	 * @return bool
	 */
	public function is($type, string $value = null): bool
	{
		if (is_array($type)) {
			foreach ($type as $realtype) {
				if ($this->is($realtype)) {
					return true;
				}
			}
			return false;
		}
		return $this->type == $type && ($value === null || $this->value == $value);
	}

	static public function getTypeName(int $type): string
	{
		return self::$typeNames[$type];
	}

	static public function getTypeFullName(int $type): string
	{
		return self::$typeFullNames[$type] ?? self::$typeNames[$type] ?? '(unknown)';
	}

	static public function addTypeName(int $type, string $name)
	{
		if (isset(self::$typeNames[$type])) {
			throw new InvalidArgumentException(sprintf(
				'Type %d has already been given the name of %s',
				$type,
				self::$typeNames[$type]
			));
		}
		self::$typeNames[$type] = $name;
	}

	static public function addTypeFullName(int $type, string $fullName)
	{
		if (isset(self::$typeFullNames[$type])) {
			throw new InvalidArgumentException(sprintf(
				'Type %d has aleady been given the full name of %s',
				$type,
				self::$typeFullNames[$type]
			));
		}
		self::$typeFullNames[$type] = $fullName;
	}
}
