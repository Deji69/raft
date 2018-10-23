<?php
namespace App\Core\PHTML;

use App\Core\PHTML\Parser\Node;
use App\Core\PHTML\Parser\Node\Raw;
use App\Core\PHTML\Parser\Node\Body;
use App\Core\PHTML\Parser\Node\Block;
use App\Core\PHTML\Parser\Node\RawPHP;
use App\Core\PHTML\Parser\Node\Layout;
use App\Core\PHTML\Parser\Node\Template;
use App\Core\PHTML\Parser\Node\Expression;
use App\Core\PHTML\Parser\Node\Expression\Variable;
use App\Core\PHTML\Parser\Node\Output as OutputNode;
use App\Core\PHTML\Parser\ExpressionParser;
use App\Core\PHTML\Exception\SyntaxError;
use App\Core\PHTML\Exception\InternalError;

class Parser
{
	/**
	 * @var Engine
	 */
	protected $engine;
	/**
	 * @var TokenStream
	 */
	protected $stream;
	/**
	 * @var Block[]
	 */
	protected $blocks = [];
	/**
	 * @var string[]
	 */
	protected $blockStack = [];
	/**
	 * @var Node[]
	 */
	protected $blockNodes = [];
	/**
	 * @var array
	 */
	protected $symbols = [];
	/**
	 * @var string
	 */
	protected $type = 'template';
	protected $library = [
		'url' => ['route', 'current']
	];

	public function __construct(Engine $engine)
	{
		$this->engine;
		$this->expressionParser = new ExpressionParser($this, $engine);
	}

	/**
	 * Returns the token stream currently being parsed.
	 *
	 * @return TokenStream
	 */
	public function getStream(): TokenStream
	{
		return $this->stream;
	}

	public function hasBlock(string $name)
	{
		return isset($this->blocks[$name]);
	}

	public function getBlock($name)
	{
		return $this->blocks[$name];
	}

	public function setBlock(string $name, Block $block)
	{
		$this->blocks[$name] = new Body([$block], [], $block->getTemplateLine());
	}

	public function getBlockStack()
	{
		return $this->blockStack;
	}

	public function peekBlockStack()
	{
		return $this->blockStack[count($this->blockStack) - 1];
	}

	public function pushBlockStack($name)
	{
		$this->blockStack[] = [
			'name' => $name,
			'nodes' => [],
		];
	}

	public function popBlockStack()
	{
		return array_pop($this->blockStack);
	}

	public function pushLocalScope()
	{
		array_unshift($this->symbols, array());
	}

	public function popLocalScope()
	{
		array_shift($this->symbols);
	}

	/**
	 * Parses a token stream into an abstract syntax tree.
	 *
	 * @param  TokenStream  $stream
	 * @return Parser\Node
	 */
	public function parse(TokenStream $stream): Node
	{
		$this->type = 'template';
		$this->stream = $stream;
		$this->nodes = [];
		$this->blocks = [];
		$this->symbols = [];
		$this->blockStack = [];
		$this->subParse();
		return $this->type == 'layout' ? new Layout($this->nodes, ['type' => $this->type]) : new Template($this->nodes, ['type' => $this->type]);
	}

	public function subParse()
	{
		$stream = $this->stream;
		while (!$this->isEnd()) {
			$stream->skipWhitespace(true);
			$node = null;
			$token = $stream->current;
			switch ($token->getType()) {
				case 'eof':
					continue;
				case 'php':
					$this->addNode(new RawPHP($token->get(), $token->getLine()));
					$stream->next();
					break;
				case 'raw':
					$this->addNode(new Raw($token->get(), $token->getLine()));
					$stream->next();
					break;
				case 'identifier':
					if ($stream->peek()->is('separator', ':')) {
						$node = $this->parseCommand();
					} elseif ($stream->peek()->is('separator', '.')) {
						$lib = $this->library;
						$whole = '';
						$stream->next(false);
						while (true) {
							if ($lib !== null && in_array($token->get(), $lib)) {
								$lib = null;
							} elseif ($lib !== null && isset($lib[$token->get()])) {
								$lib = $lib[$token->get()];
							} else {
								if ($whole) {
									throw new SyntaxError('\''.$token->get().'\' is not a member of \''.$whole.'\'', $token->getLine(), $token->getSource());
								} else {
									throw new SyntaxError('Unexpected \''.$token->get().'\'', $token->getLine(), $token->getSource());
								}
							}
							$whole .= ($whole ? '.' : '').$token->get();
							$stream->next(false);
							var_dump($stream->peek());

							if (!$stream->peek()->is('separator')) {
								break;
							}
							$token = $stream->next(false);
							var_dump($token);
						}
						var_dump($lib);
						die;
						var_dump($stream->peek());
						var_dump($lib);
						die($whole);
						die('implement .');
					}
					//break;
				case 'operator':
					if ($node === null) {
						$node = $this->expressionParser->parse();
					}
					if ($node !== false) {
						if ($node instanceof Expression && !$node->doesAssign()) {
							$node = new OutputNode($node, $node->getTemplateLine());
						}

						$this->addNode($node);
					}
					break;
				default:
					throw new InternalError('Invalid token: '.$token->getType());
			}
		}
	}

	public function parseCommand()
	{
		$stream = $this->stream;
		$cmd = $stream->expect('identifier');
		$stream->expect('separator', ':');
		$id = $stream->expect('identifier');
		$lineno = $cmd->getLine();

		switch ($cmd->get()) {
			case 'template':
				if ($lineno != 1) {
					throw new SyntaxError('The layout command must appear on the first line.', $lineno, $stream->getSourceContext());
				}
				$this->type = $id->get();
				return false;
			case 'begin':
				$name = $id->get();
				if ($this->hasBlock($name)) {
					throw new SyntaxError('The block \''.$name.'\' has already been defined line '.$this->getBlock($name)->getTemplateLine().'.', $stream->current->getLine(), $stream->getSource());
				}
				$this->pushBlockStack($id->get());
				return false;
			case 'end':
				$block = $this->popBlockStack();
				$name = $block['name'];
				if ($id->get() != $name) {
					throw new SyntaxError('End of block mismatch, expected \''.$name.'\' defined on line '.$this->getBlock($name)->getTemplateLine().' but found \''.$id->get().'\'', $lineno);
				}
				$this->setBlock($name, $block = new Block($name, new Body($block['nodes']), $lineno));
				return new Node\BlockReference($name, $block, $lineno, 'block');
			case 'block':
				return new Node\BlockReference($id->get(), null, $lineno, 'block');
		}
		print_r($cmd);
		print_r($this->stream->current);
		die('Parser.php ('.__LINE__.')');
		return null;
	}

	protected function addNode(Node $node)
	{
		if (empty($this->blockStack)) {
			$this->nodes[] = $node;
		} else {
			$this->blockStack[count($this->blockStack) - 1]['nodes'][] = $node;
		}
	}

	protected function isEnd()
	{
		return $this->stream->isEnd();
	}
}
