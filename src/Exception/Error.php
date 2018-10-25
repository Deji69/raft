<?php
namespace Raft\Exception;

use Exception;
use Raft\Source;

class Error extends Exception
{
	protected $lineno;
	protected $name;
	protected $rawMessage;
	protected $sourcePath;
	protected $sourceCode;

	/**
	 * Constructor.
	 *
	 * Set both the line number and the name to false to
	 * disable automatic guessing of the original template name
	 * and line number.
	 *
	 * Set the line number to -1 to enable its automatic guessing.
	 * Set the name to null to enable its automatic guessing.
	 *
	 * By default, automatic guessing is enabled.
	 *
	 * @param string 				$message 	The error message
	 * @param int|null				$lineno 	The template line where the error occurred
	 * @param Source|string|null 	$source 	The source context where the error occurred
	 * @param Exception 			$previous 	The previous exception
	 */
	public function __construct(string $message, int $lineno = null, $source = null, Exception $previous = null)
	{
		parent::__construct('', 0, $previous);

		if (null === $source) {
			$name = null;
		} elseif (!$source instanceof Source) {
			$name = $source;
		} else {
			$name = $source->getName();
			$this->sourceCode = $source->getCode();
			$this->sourcePath = $source->getPath();
		}

		$this->lineno = $lineno;
		$this->name = $name;
		$this->rawMessage = $message;

		$this->updateRepr();
	}

	/**
	 * Gets the raw message.
	 *
	 * @return string The raw message
	 */
	public function getRawMessage()
	{
		return $this->rawMessage;
	}

	/**
	 * Gets the template line where the error occurred.
	 *
	 * @return int The template line
	 */
	public function getTemplateLine()
	{
		return $this->lineno;
	}

	/**
	 * Sets the template line where the error occurred.
	 *
	 * @param int $lineno The template line
	 */
	public function setTemplateLine(int $lineno)
	{
		$this->lineno = $lineno;

		$this->updateRepr();
	}

	/**
	 * Gets the source context of the Twig template where the error occurred.
	 *
	 * @return Source|null
	 */
	public function getSourceContext()
	{
		return $this->name ? new Source($this->sourceCode, $this->name, $this->sourcePath) : null;
	}

	/**
	 * Sets the source context of the Twig template where the error occurred.
	 */
	public function setSourceContext(Source $source = null)
	{
		if (null === $source) {
			$this->sourceCode = $this->name = $this->sourcePath = null;
		} else {
			$this->sourceCode = $source->getCode();
			$this->name = $source->getName();
			$this->sourcePath = $source->getPath();
		}

		$this->updateRepr();
	}

	public function appendMessage($rawMessage)
	{
		$this->rawMessage .= $rawMessage;
		$this->updateRepr();
	}

	private function updateRepr()
	{
		$this->message = $this->rawMessage;

		if ($this->sourcePath && $this->lineno > 0) {
			$this->file = $this->sourcePath;
			$this->line = $this->lineno;

			return;
		}

		$dot = false;
		if ('.' === substr($this->message, -1)) {
			$this->message = substr($this->message, 0, -1);
			$dot = true;
		}

		$questionMark = false;
		if ('?' === substr($this->message, -1)) {
			$this->message = substr($this->message, 0, -1);
			$questionMark = true;
		}

		if ($this->name) {
			if (is_string($this->name) || (is_object($this->name) && method_exists($this->name, '__toString'))) {
				$name = sprintf('"%s"', $this->name);
			} else {
				$name = json_encode($this->name);
			}
			$this->message .= sprintf(' in %s', $name);
		}

		if ($this->lineno && $this->lineno >= 0) {
			$this->message .= sprintf(' at line %d', $this->lineno);
		}

		if ($dot) {
			$this->message .= '.';
		}

		if ($questionMark) {
			$this->message .= '?';
		}
	}
}
