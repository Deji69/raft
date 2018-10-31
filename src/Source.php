<?php
namespace Raft;

class Source
{
	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string[]
	 */
	protected $lines = [];

	/**
	 * @var int[]
	 */
	protected $lineOffsets = [];

	/**
	 * @var int[]
	 */
	protected $lineCharCount = [];

	/**
	 * @param string $code The template source code.
	 * @param string $name The template logical name.
	 * @param string $path The filesystem path of the template.
	 */
	public function __construct(string $code, string $name, string $path = '')
	{
		$this->code = $code;
		$this->name = $name;
		$this->path = $path;

		$lines = preg_split('#\n#u', $code, 10000000, PREG_SPLIT_OFFSET_CAPTURE);
		$mbOffset = 0;

		foreach ($lines as $line) {
			$this->lines[] = $line[0];
			$this->lineOffsets[] = $line[1];
			$this->lineCharCount[] = $mbOffset;
			$mbOffset += mb_strwidth($line[0]) + 1;
		}
	}

	/**
	 * Returns the source code.
	 *
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * Returns the logical name of the source.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Returns the file path of the source, if any.
	 *
	 * @return string|null
	 */
	public function getPath(): ?string
	{
		return $this->path;
	}

	/**
	 * Gets a range of the source code or null if the requested range is invalid.
	 * If length is not specified, gets code from $start up to the first whitespace or code end.
	 *
	 * @param  int 		$start
	 * @param  int|null $length
	 * @return string|null
	 */
	public function getToken(int $start, int $length = null): ?string
	{
		if ($start < 0) {
			return null;
		}
		if ($length === null) {
			if (preg_match('#\s#A', $this->code, $m, PREG_OFFSET_CAPTURE, $start)) {
				$length = $m[0][1] - $start;
			}
		}
		if ($start + $length > $this->getLength()) {
			return null;
		}
		return substr($this->code, $start, $length);
	}

	/**
	 * Gets the total size of the source code in unicode characters.
	 *
	 * @return int
	 */
	public function getLength(): int
	{
		return strlen($this->code);
	}

	/**
	 * Gets the source offset for the line.
	 *
	 * @param  int $line
	 * @return int|null
	 */
	public function getOffsetForLine(int $line): ?int
	{
		return isset($this->lineOffsets[$line - 1]) ? $this->lineOffsets[$line - 1] : null;
	}

	/**
	 * Gets the line number of the offset.
	 *
	 * @param  int $offset
	 * @return int
	 */
	public function getLineForOffset(int $offset): int
	{
		foreach ($this->lineOffsets as $index => $v) {
			if ($v > $offset) {
				return $index;
			}
		}
		return count($this->lineOffsets);
	}

	/**
	 * Gets the line column number of the offset.
	 *
	 * @param  int $offset
	 * @return int
	 */
	public function getColumnForOffset(int $offset): int
	{
		if ($line = $this->getLineForOffset($offset)) {
			$lineOffset = $this->getOffsetForLine($line);
			$portion = substr($this->lines[$line - 1], 0, $offset - $lineOffset);
			return mb_strlen($portion) + 1;
		}
		return $offset + 1;
	}
}
