<?php
namespace Raft;

use Raft\Token;
use Raft\Engine;
use Raft\TokenStream;
use Raft\Exception\SyntaxError;
use InvalidArgumentException;

/**
 * Takes template code and converts it into a TokenStream.
 */
class Lexer
{
	use Lexer\LexesOperator;

	const STATE_DATA = 0;
	const STATE_TAG = 1;
	const STATE_BLOCK = 2;

	/**
	 * The source file currently being lexed.
	 *
	 * @var Source
	 */
	protected $source;

	/**
	 * @var Token[]
	 */
	protected $tokens = [];

	/**
	 * @var callable[]
	 */
	protected $states = [];

	/**
	 * @var callable
	 */
	protected $state = self::STATE_DATA;

	/**
	 * @var array
	 */
	protected $rules = [];

	/**
	 * @var int
	 */
	protected $cursor = 0;

	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var string[]
	 */
	protected $tokenTypes = [];

	/**
	 * @var string
	 */
	protected $tag;

	/**
	 * @var array
	 */
	protected $tagStack;

	/**
	 * @var array
	 */
	protected $delimiters = [
		'Comment' => ['{#', '#}'],
		'Tag' => ['{{', '}}'],
	];

	/**
	 * @var array
	 */
	protected $tagRules = [];

	/**
	 * @var string
	 */
	protected $tagStartRegex;

	/**
	 * @var array
	 */
	protected $tagPositions = [];

	/**
	 * @var array
	 */
	protected $delimStack = [];

	/**
	 * @param Engine $engine
	 */
	public function __construct(Engine $engine, array $options = [])
	{
		$this->delimiters = array_merge($this->delimiters, $options['delimiters'] ?? []);

		// tag rules
		$pregQuotedTagStarts = [];

		foreach ($this->delimiters as $type => $delims) {
			$startPreg = preg_quote($delims[0], '#');
			$pregQuotedTagStarts[] = $startPreg;
			$this->tagRules[$delims[0]] = [
				'begin' => $delims[0],
				'end' => $delims[1],
				'type' => strtolower($type),
				'state' => [$this, 'state'.$type],
			];
		}

		$this->tagStartRegex = '#'.implode('|', $pregQuotedTagStarts).'#';

		// default rules
		$this->rules = [
			// we can have regex rules obviously
			Token::WHITESPACE	=> '#[\h]+#A',
			Token::NEWLINE		=> '#[\n]+#A',
			Token::IDENTIFIER	=> '#[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*#A',
			Token::NUMBER		=> '#[+\-]?(?:0|[1-9]\d*)(?:\.\d*)?(?:[eE][+\-]?\d+)?#A',
			// but they can also be callables
			Token::STRING		=> [$this, 'lexString'],
			Token::DELIMITER	=> [$this, 'lexDelimiter'],
			Token::OPERATOR		=> [$this, 'lexOperator'],
			Token::SEPARATOR	=> [$this, 'lexSeparator'],
			// callables look kinda like this
			Token::END			=> function (string $src, int $pos) {
				$rule = '#\s*('.preg_quote($this->tag['end'], '#').')#As';
				if (preg_match($rule, $src, $m, PREG_OFFSET_CAPTURE, $pos)) {
					return $m[0][1] + strlen($m[0][0]);
				}
				return false;
			},
		];
	}

	/**
	 * Add a token to the output stream.
	 *
	 * @param Token $token
	 */
	public function addToken(Token $token)
	{
		$this->tokens[] = $token;
		return $token;
	}

	/**
	 * Create a token
	 *
	 * @param int 	$type
	 * @param int 	$cursor
	 * @param int 	$length
	 */
	public function createToken(int $type, int $cursor = null, int $length = null)
	{
		if ($cursor === null) {
			return $this->createRawToken($type);
		}
		return $this->createRawToken($type, $this->source->getToken($cursor, $length), $cursor);
	}

	/**
	 * Create a token with custom data.
	 *
	 * @param int 		$type
	 * @param string 	$data
	 * @param int 		$cursor
	 */
	public function createRawToken(int $type, string $data = null, int $cursor = null)
	{
		return new Token($type, $data, $cursor);
	}

	/**
	 * Lex the given template contents.
	 *
	 * @param  Source|string  $value
	 * @return TokenStream
	 */
	public function tokenize($source): TokenStream
	{
		if (is_string($source)) {
			$source = new Source($source, 'noname');
		} elseif (!$source instanceof Source) {
			throw new InvalidArgumentException('Expected string or instance of '.Source::class);
		}

		Engine::getActiveEngine()->setSource($source);

		$this->tokens = [];
		$this->source = $source;
		$this->cursor = 0;
		$this->code = $source->getCode();
		$this->end = $source->getLength();

		if (preg_match_all($this->tagStartRegex, $this->code, $tagPositions, PREG_OFFSET_CAPTURE)) {
			$this->tagPositions = $tagPositions[0];
		}

		$token = null;
		$this->state = [[$this, 'stateData']];

		while ($this->cursor < $this->end) {
			$token = call_user_func(end($this->state), $token);
			$type = $token ? $token->getType() : Token::END;
			switch ($type) {
				case Token::BEGIN:
					$this->tag = $this->tagRules[$token->getValue()];
					$this->cursor = $token->getOffsetEnd();
					$this->tagStack[] = $this->tag;
					$this->state[] = $this->tag['state'];
					break;
				case Token::END:
					array_pop($this->state);
					array_pop($this->tagStack);
					break;
				default:
					break;
			}
		}

		$this->addToken($this->createToken(Token::EOF, $this->end, 0));
		return new TokenStream($this->tokens, $this->source);
	}

	/**
	 * Lexer state during raw template data.
	 */
	protected function stateData()
	{
		// if there are no more tags, tokenize the rest of the contents as raw template data
		if (empty($this->tagPositions)) {
			if ($this->cursor < $this->end) {
				$this->addToken(
					$this->createRawToken(Token::RAW, substr($this->code, $this->cursor), $this->cursor)
				);
				$this->cursor = $this->end;
			}
			return null;
		}

		// get the next tag position and tokenize up to it as raw template data
		$tagPosition = array_shift($this->tagPositions);
		if ($tagPosition[1] > $this->cursor) {
			$this->addToken(
				$this->createToken(Token::RAW, $this->cursor, $tagPosition[1] - $this->cursor)
			);
		}
		return $this->createToken(Token::BEGIN, $tagPosition[1], strlen($tagPosition[0]));
	}

	/**
	 * Lexer state during a comment.
	 */
	protected function stateComment()
	{
		$token = $this->lex([
			Token::END => '#.*('.preg_quote($this->tag['end'], '#').')#As'
		]);

		// absorb a single newline following a comment
		$this->lexOneOf([Token::NEWLINE]);
		return null;
	}

	/**
	 * Lexer state inside a tag.
	 */
	protected function stateTag(Token $token = null)
	{
		static $rules = [
			Token::WHITESPACE,
			Token::NEWLINE,
			Token::END,
			Token::NUMBER,
			Token::OPERATOR,
			Token::SEPARATOR,
			Token::STRING,
			Token::DELIMITER,
			Token::IDENTIFIER,
		];
		if ($token) {
			// ignore whitespace and newlines in tags
			if (!$token->is([
				Token::WHITESPACE,
				Token::NEWLINE,
			])) {
				$this->addToken($token);
			}
		}
		if ($token = $this->lex($rules)) {
			if ($token->is(Token::STRING)) {
				$str = substr($token->getValue(), 1, -1);
				$token = $this->createRawToken(Token::STRING, $str, $token->getOffset());
			}
			elseif ($token->is(Token::END)) {
				$this->addToken($token);
				return null;
			}
		}
		return $token;
	}

	/**
	 * Lexes current code position using any of the given token lexers.
	 * Returns a Token or throws an exception.
	 *
	 * @return Token
	 * @throws SyntaxError
	 */
	protected function lex(array $tokenTypes): Token
	{
		if ($token = $this->lexOneOf($tokenTypes)) {
			return $token;
		}
		$token = $this->source->getToken($this->cursor);
		throw new SyntaxError('Unexpected "'.$token.'"', $this->cusor, $this->source);
	}

	/**
	 * Lexes current code position using any of the given token lexers.
	 * Returns a Token or null of nothing matched.
	 *
	 * @param  array $tokenTypes	An array containing Token::Type => Rule assignments.
	 * 								[Token::Type => string]:
	 * 								  string treated as a regex rule using preg_match.
	 * 								  Rule should use the A(nchored) flag.
	 * 								  If the rule uses captures, the first is taken.
	 * 								[Token::Type => callable]:
	 * 								  callable called with the code and cursor position
	 * 								  should return the cursor position after the lexed token.
	 * 								  Signature: callable(string, int): int
	 * 								[Token::Type]
	 * 								  The default lexer rule for the given type will be used
	 * 								  defined in $this->rules.
	 *
	 * @return Token|null
	 */
	protected function lexOneOf(array $tokenTypes): ?Token
	{
		$token = null;
		foreach ($tokenTypes as $type => $rule) {
			$endPos = $this->cursor;

			if (!is_string($rule)) {
				if (!is_array($rule)) {
					// use default rules
					$type = $rule;
					$rule = $this->rules[$type];
				} else {
					// accept as array containing a rule for forward-compat
					$rule = $rule['rule'];
				}
			}

			if (is_string($rule)) {
				// try a regex rule
				if (preg_match($rule, $this->code, $m, PREG_OFFSET_CAPTURE, $this->cursor)) {
					$m = isset($m[1]) ? $m[1] : $m[0];
					$endPos = $m[1] + strlen($m[0]);
					$token = $this->createRawToken($type, $m[0], $m[1]);
					$this->cursor = $endPos;
					break;
				}
			} elseif (is_callable($rule)) {
				// try a custom rule via callable
				$endPos = $rule($this->code, $this->cursor);
			}

			if ($endPos > $this->cursor) {
				$token = $this->createToken($type, $this->cursor, $endPos - $this->cursor);
				$this->cursor = $endPos;
				$unknown = false;
				break;
			}
		}
		return $token;
	}

	protected function lexDelimiter($src, $pos) {
		static $delimiterTable = [
			'(', ')', '[', ']'
		];
		if (in_array($src[$pos], $delimiterTable)) {
			return $pos + 1;
		}
		return false;
	}

	protected function lexSeparator($src, $pos) {
		static $separatorTable = [
			'?', ':', ',', '.', '|',
		];
		if (in_array($src[$pos], $separatorTable)) {
			return $pos + 1;
		}
		return false;
	}

	protected function lexString($src, $pos) {
		static $stringDelimiters = ['\'', '\''];
		if ($src[$pos] == $stringDelimiters[0]) {
			$startPos = $pos;

			// skip the delimiter char and find the first matching delimiter after it
			while (($pos = strpos($src, $stringDelimiters[1], ++$pos)) !== false) {
				$rpos = $pos - 1;
				// if the last character pos is where we started, we have an "" (empty string situation)
				if ($rpos == $startPos) {
					$slashCount = 0;
				} else {
					// count the number of consecutive \ (backslash) chars preceeding the ending delimiter
					while ($src[$rpos] == '\\') {
						--$rpos;
					}
					$slashCount = $pos - $rpos - 1;
				}
				// if the number of backslash chars is 0 or divisible by 2, $pos is the end delimiter
				if ($slashCount % 2 == 0) {
					return $pos + 1;
				}
			}
		}
		return false;
	}
}
