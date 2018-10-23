<?php
namespace App\Core\PHTML\Parser;

use App\Core\PHTML\Engine;
use App\Core\PHTML\Parser;
use App\Core\PHTML\Lexer\Token;
use App\Core\PHTML\TokenStream;
use App\Core\PHTML\Exception\InternalError;

class ExpressionParser
{
	const OPERATOR_LEFT = 1;
	const OPERATOR_RIGHT = 2;

	/**
	 * @var Parser
	 */
	protected $parser;

	/**
	 * @var Engine
	 */
	protected $engine;

	/**
	 * @var TokenStream
	 */
	protected $stream;

	protected $operators = [
		'Unary' => [
			'!'   => [],
			'+'   => [],
			'-'   => [],
		],
		'Binary' => [
			'='   => ['precedence' => 50,	'class' => Node\Expression\Binary\Assign::class,	'associativity' => self::OPERATOR_LEFT],
			'+'   => [],
			'-'   => [],
			'*'   => [],
			'/'   => [],
			'%'   => [],
			'^'   => [],
			'~'   => [],
			'|'   => [],
			'&'   => [],
			'<'   => [],
			'>'   => [],
			'<<'  => [],
			'>>'  => [],
			'>='  => [],
			'<='  => [],
			'=='  => [],
			'!='  => [],
			'**'  => [],
			'??'  => ['precedence' => 100,	'class' => Node\Expression\Binary\NullCoalesce::class,		'associativity' => self::OPERATOR_RIGHT],
		],
	];

	public function __construct(Parser $parser, Engine $engine)
	{
		$this->parser = $parser;
		$this->engine = $engine;
	}

	public function parse(int $precedence = 0)
	{
		$this->stream = $this->parser->getStream();
		$expr = $this->parseExpression();
		$token = $this->stream->current;

		while ($this->isBinary($token) && $this->operators['Binary'][$token->get()]['precedence'] >= $precedence) {
			$op = $this->operators['Binary'][$token->get()];

			$this->stream->next();

			$expr1 = $this->parseExpression($op['associativity'] === self::OPERATOR_LEFT ? $op['precedence'] + 1 : $op['precedence']);
			$class = $op['class'];
			$expr = new $class($expr, $expr1, $token->getLine());

			$token = $this->stream->current;
		}
		return $expr;
	}

	protected function parseExpression()
	{
		$token = $this->stream->current;
		switch ($token->getType()) {
			case 'identifier':
				$this->stream->next();
				$constant = '';
				switch (strtolower($token->get())) {
					case 'true':
						$constant = true;
						break;
					case 'false':
						$constant = false;
						break;
					case 'null':
						$constant = null;
						break;
				}
				if (!is_string($constant) && !empty($constant)) {
					$node = new Node\Expression\Constant($constant, $token->getLine());
				} else if ($this->stream->current->get() == '(') {
					throw new InternalError('plz implement (');
					//$node = $this->getFunctionNode($token->get(), $token->getLine());
				} else {
					$node = new Node\Expression\Variable($token->get(), $token->getLine());
				}
				break;
			case 'string':
				$node = $this->parseStringExpression();
				break;
			default:
				print_r($token);
				die('ExpressionParser.php ('.__LINE__.')');
				break;
		}
		return $node;
	}

	protected function parseStringExpression()
	{
		$stream = $this->stream;

		$nodes = array();

		// a string cannot be followed by another string in a single expression
		$nextCanBeString = true;
		while (true) {
			if ($nextCanBeString && $token = $stream->nextIf('string')) {
				$nodes[] = new Node\Expression\Constant($token->get(), $token->getLine());
				$nextCanBeString = false;
			} else {
				break;
			}
		}

		$expr = array_shift($nodes);
		foreach ($nodes as $node) {
			$expr = new Node\Expression\Binary\Concat($expr, $node, $node->getTemplateLine());
		}
		return $expr;
	}

	protected function isBinary(Token $token)
	{
		return $token->is('operator') && isset($this->operators['Binary'][$token->get()]);
	}

	protected function isUnary(Token $token)
	{
		return $token->is('operator') && isset($this->operators['Unary'][$token->get()]);
	}
}
