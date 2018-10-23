<?php
namespace App\Core\PHTML;

class Source
{
	/**
	 * Source code.
	 *
	 * @var string
	 */
	protected $code;

	/**
	 * Name of source file.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Path of source file.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Line offsets.
	 *
	 * @var int[]
	 */
	protected $lineOffsets;

	/**
	 * @param string $code 	The template source code.
	 * @param string $name 	The template logical name.
	 * @param string $path 	The filesystem path of the template.
	 */
	public function __construct(string $code, string $name, string $path = '')
	{
		$this->code = $code;
		$this->name = $name;
		$this->path = $path;
		$this->lineOffsets[] = 0;
		if (preg_match_all('#\n#', $this->code, $matches, PREG_OFFSET_CAPTURE)) {
			foreach ($matches[0] as $match) {
				$this->lineOffsets[] = $match[1] + 1;
			}
		}
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getToken(int $start, int $length): string
	{
		return mb_substr($this->code, $start, $length);
	}

	public function getOffsetForLine(int $line): int
	{
		return isset($this->lineOffsets[$line - 1]) ? $this->lineOffsets[$line - 1] : false;
	}

	public function getLineForOffset(int $offset): int
	{
		foreach ($this->lineOffsets as $index => $v) {
			if ($v >= $offset) {
				return $index;
			}
		}
		return count($this->lineOffsets) + 1;
	}
}
