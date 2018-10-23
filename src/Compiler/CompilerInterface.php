<?php
namespace App\Core\PHTML\Compiler;

interface CompilerInterface
{
	/**
	 * Add raw string to the compiled code.
	 *
	 * @param  string $string
	 * @return this
	 */
	public function raw(string $string): self;

	/**
	 * Writes lines to the compiled code, adding indentation.
	 *
	 * @param  string... $strings
	 * @return $this
	 */
	public function write(string... $strings): self;

	/**
	 * Adds a quoted string to the compiled code.
	 *
	 * @param string $value The string
	 *
	 * @return $this
	 */
	public function string($value): self;
}
