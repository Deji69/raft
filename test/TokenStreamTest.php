<?php
namespace Raft\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Raft\Lexer\Token;
use Raft\Lexer\TokenStream;

class TokenStreamTest extends TestCase
{
	protected static $tokens;

	public function tearDown()
	{
		m::close();
	}

	public function testNext()
	{
		$stream = new TokenStream(self::$tokens);
		$out = [];
		while (!$stream->isEOF()) {
			$token = $stream->next();
			$out[] = $token->getValue();
		}
		$this->assertEquals(
			'1, 2, 3, 4, 5, 6, 7',
			implode(', ', $out),
			'->next() returns current token and advances internal pointer'
			.",\n".'->isEOF() returns true at the end of the stream'
		);
	}

	public function testSkipWhitespace()
	{
		$stream = new TokenStream([
			new Token(Token::WHITESPACE),
			new Token(Token::WHITESPACE),
			new Token(Token::WHITESPACE),
			new Token(Token::RAW)
		]);
		$stream->skipWhitespace();
		$this->assertTrue($stream->current->is(Token::RAW), '->skipWhitespace() skips all whitespace');

		$stream = new TokenStream([
			new Token(Token::WHITESPACE),
			new Token(Token::NEWLINE),
			new Token(Token::WHITESPACE),
			new Token(Token::NEWLINE),
			new Token(Token::WHITESPACE),
			new Token(Token::RAW)
		]);
		$stream->skipWhitespace(true);
		$this->assertTrue($stream->current->is(Token::RAW), '->skipWhitespace(true) all whitespace and newlines');
	}

	/**
	 * @expectedException			Raft\Exception\SyntaxError
	 * @expectedExceptionMessage	Unexpected end of template.
	 */
	public function testExceptionNextUnexpectedEOF()
	{
		$stream = new TokenStream([
			new Token(Token::RAW)
		]);
		while (!$stream->isEOF()) {
			$stream->next();
		}
	}

	/**
	 * @expectedException			Raft\Exception\SyntaxError
	 * @expectedExceptionMessage	Unexpected end of template.
	 */
	public function testExceptionPeekUnexpectedEOF()
	{
		$stream = new TokenStream([
			new Token(Token::RAW)
		]);
		$stream->next();
		$stream->peek();
	}

	protected function setUp()
	{
		self::$tokens = [
			new Token(Token::RAW, 1, 1),
			new Token(Token::RAW, 2, 1),
			new Token(Token::RAW, 3, 1),
			new Token(Token::RAW, 4, 1),
			new Token(Token::RAW, 5, 1),
			new Token(Token::RAW, 6, 1),
			new Token(Token::RAW, 7, 1),
			new Token(Token::EOF, 0, 1)
		];
	}
}
