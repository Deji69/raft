<?php
namespace App\Core\PHTML\Parser;

use Countable;
use ArrayAccess;
use ArrayIterator;
use LogicException;
use IteratorAggregate;
use App\Core\PHTML\Parser;
use App\Core\PHTML\Compiler;
use InvalidArgumentException;

class Node implements ArrayAccess, Countable, IteratorAggregate
{
	protected $nodes = [];
	protected $attributes = [];
	protected $lineno = 0;

	public function __construct(array $nodes = [], $attributes = [], int $line = 0)
	{
		foreach ($nodes as $name => $node) {
			if (!$node instanceof self) {
				throw new InvalidArgumentException(get_class($node).' is an unsupported child node');
			}
		}

		$this->nodes = $nodes;
		$this->attributes = is_string($attributes) ? ['data' => $attributes] : $attributes;
		$this->lineno = $line;
	}

	public function compile(Compiler $compiler)
	{
		foreach ($this->nodes as $node) {
			$node->compile($compiler);
		}
	}

	public function getTemplateLine()
	{
		return $this->lineno;
	}

	/**
	 * @return bool
	 */
	public function hasAttribute($name)
	{
		return array_key_exists($name, $this->attributes);
	}

	/**
	 * @return mixed
	 */
	public function getAttribute($name)
	{
		if (!array_key_exists($name, $this->attributes)) {
			throw new LogicException(sprintf('Attribute "%s" does not exist for Node "%s".', $name, get_class($this)));
		}

		return $this->attributes[$name];
	}

	/**
	 * @param string $name
	 * @param mixed  $value
	 */
	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}

	public function removeAttribute($name)
	{
		unset($this->attributes[$name]);
	}

	public function hasNode(string $name)
	{
		return isset($this->nodes[$name]);
	}

	public function setNode(string $name, Node $value)
	{
		$this->nodes[$name] = $value;
	}

	public function getNode(string $name): Node
	{
		if (!isset($this->nodes[$name])) {
			throw new LogicException(sprintf('Node "%s" does not exist for Node "%s".', $name, get_class($this)));
		}
		return $this->nodes[$name];
	}

	public function removeNode(string $name)
	{
		unset($this->nodes[$name]);
	}

	public function count()
	{
		return count($this->nodes);
	}

	public function getIterator()
	{
		return new ArrayIterator($this->nodes);
	}

	public function offsetExists($offset): bool
	{
		return $this->hasNode($offset);
	}

	public function offsetGet($offset): Node
	{
		return $this->getNode($offset);
	}

	public function offsetSet($offset, $value)
	{
		$this->setNode($offset, $value);
	}

	public function offsetUnset($offset)
	{
		$this->removeNode($offset);
	}
}
