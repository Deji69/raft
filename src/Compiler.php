<?php
namespace Raft;

use Closure;
use InvalidArgumentException;
use Raft\Engine;
use Raft\Parser\Node;
use Raft\Parser\TokenStream;
use Raft\Exceptions\SyntaxError;

class Compiler
{
	/**
	 * The indentation level
	 *
	 * @var int
	 */
	protected $indentation;

	/**
	 * The compiled template output
	 *
	 * @var string
	 */
	protected $output;

	/**
	 * The file currently being compiled.
	 *
	 * @var Engine
	 */
	protected $engine;

	public function __construct(Engine $engine)
	{
		$this->engine = $engine;
	}

	public function getOutput(): string
	{
		return $this->output;
	}

	public function writeHeader()
	{
		$this->write("<?php\n");
		if ($this->type == 'template') {
			$this
				->write("\$template = function () {\n")
				->indent()
			;
		}
	}

	public function writeFooter()
	{
		if ($this->type == 'template') {
			$this
				->outdent()
				->write("};\n")
			;
		}
	}

	/**
	 * Compiles the AST into a PHP template.
	 *
	 * @param  Node 	$node
	 * @param  int 		$indentation
	 * @return string
	 */
	public function compile(Node $node, int $indentation = 0)
	{
		$this->type = $node->getAttribute('type');
		$this->output = '';
		$this->indentation = $indentation;
		$node->compile($this);
		return $this;
	}

	public function subcompile(Node $node, bool $raw = true)
	{
		if (false === $raw) {
			$this->output .= str_repeat(' ', $this->indentation * 4);
		}
		$node->compile($this);
		return $this;
	}

	/**
	 * Indents the generated code.
	 *
	 * @param int $step The number of indentation to add
	 *
	 * @return $this
	 */
	public function indent($step = 1)
	{
		$this->indentation += $step;
		return $this;
	}

	/**
	 * Outdents the generated code.
	 *
	 * @param int $step The number of indentation to remove
	 *
	 * @return $this
	 *
	 * @throws LogicException When trying to outdent too much so the indentation would become negative
	 */
	public function outdent($step = 1)
	{
		// can't outdent by more steps than the current indentation level
		if ($this->indentation < $step) {
			throw new LogicException('Unable to call outdent() as the indentation would become negative.');
		}
		$this->indentation -= $step;
		return $this;
	}

	/**
	 * Writes a raw string to the compiled code.
	 *
	 * @param string $string The string
	 *
	 * @return $this
	 */
	public function raw($string)
	{
		$this->output .= $string;
		return $this;
	}

	/**
	 * Writes a string to the compiled code by adding indentation.
	 *
	 * @return $this
	 */
	public function write(...$strings)
	{
		foreach ($strings as $string) {
			$this->output .= str_repeat(' ', $this->indentation * 4).$string;
		}
		return $this;
	}

	/**
	 * Writes a quoted string.
	 *
	 * @param string $value The string
	 *
	 * @return $this
	 */
	public function string(string $value)
	{
		return $this->raw('"'.addcslashes($value, "\0\t\r\n\"\$\\").'"');
	}

	public function output($fn)
	{
		$this->write('$this->output(');
		if ($fn instanceof Closure) {
			$fn($this);
		} elseif ($fn instanceof Node) {
			$this->subcompile($fn, true);
		} elseif (is_string($fn)) {
			$this->string($fn);
		} else {
			throw new InvalidArgumentException('$fn not a valid type for output method');
		}
		$this->raw(");\n");
		return $this;
	}

	/**
	 * Writes a variable with the specified name.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function variable(string $name)
	{
		$this->write('$this->vars[\''.$name.'\']');
		return $this;
	}

	/**
	 * Writes a PHP representation of a given value.
	 *
	 * @param mixed $value The value to convert
	 *
	 * @return $this
	 */
	public function repr($value)
	{
		if (is_int($value) || is_float($value)) {
			if (false !== $locale = setlocale(LC_NUMERIC, '0')) {
				setlocale(LC_NUMERIC, 'C');
			}

			$this->raw($value);

			if (false !== $locale) {
				setlocale(LC_NUMERIC, $locale);
			}
		} elseif (null === $value) {
			$this->raw('null');
		} elseif (is_bool($value)) {
			$this->raw($value ? 'true' : 'false');
		} elseif (is_array($value)) {
			$this->raw('array(');
			$first = true;
			foreach ($value as $key => $v) {
				if (!$first) {
					$this->raw(', ');
				}
				$first = false;
				$this->repr($key);
				$this->raw(' => ');
				$this->repr($v);
			}
			$this->raw(')');
		} else {
			$this->string($value);
		}
		return $this;
	}
}
