<?php
namespace Raft\Tests;

use Mockery as m;
use Raft\Lexer;
use Raft\Engine;
use Raft\Lexer\Token;
use Raft\Exception\SyntaxError;
use PHPUnit\Framework\TestCase;

class LexerTest extends TestCase
{
	public function tearDown()
	{
		m::close();
	}

	public function testIdentifierReference()
	{
		$this->runTokenTestLoop([
			[
				'test' => 'Empty expression',
				'code' => '{{}}',
				'expects' => [
					[Token::BEGIN, '{{'],
					[Token::END, '}}'],
				],
			],
			[
				'test' => 'Single unicode identifier tag with no spaces',
				'code' => '{{§}}',
				'expects' => [
					[Token::BEGIN, '{{'],
					[Token::IDENTIFIER, '§'],
					[Token::END, '}}'],
				],
			],
			[
				'test' => 'Single unicode identifier tag',
				'code' => '{{ § }}',
				'expects' => [
					[Token::BEGIN, '{{'],
					[Token::IDENTIFIER, '§'],
					[Token::END, '}}'],
				],
			],
		]);
	}

	public function testUnterminiatedTagException()
	{
		$this->expectException(SyntaxError::class);
		$this->expectExceptionMessage(
			'Unterminated tag at end-of-file ("{{" started on line 1), expecting "}}" in "noname" on line 1'
		);
		$this->runTokenTestLoop([
			[
				'test' => 'Unterminated tags throw exceptions',
				'code' => '{{',
			]
		]);

	}

	public function testUnterminatedCommentException()
	{
		$this->expectException(SyntaxError::class);
		$this->expectExceptionMessage(
			'Unterminated comment at end-of-file ("{#" started on line 1), expecting "#}" in "noname" on line 2'
		);
		$this->runTokenTestLoop([
			[
				'test' => 'Unterminated comments throw exceptions',
				'code' => '{# test
							  test'
			]
		]);
	}

	public function testBlockDefinition()
	{
		$this->runTokenTestLoop([
			[
				'test' => 'Lexing a block of raw template data',
				'code' => [
					'{{begin:test}}ABC{{end:test}}',
					'{{begin:test}}
					ABC
					{{end:test}}',
				],
				'expects' => [
					[Token::BEGIN, '{{'],
					[Token::IDENTIFIER, 'begin'],
					[Token::SEPARATOR, ':'],
					[Token::IDENTIFIER, 'test'],
					[Token::END, '}}'],
					[Token::RAW],
					[Token::BEGIN, '{{'],
					[Token::IDENTIFIER, 'end'],
					[Token::SEPARATOR, ':'],
					[Token::IDENTIFIER, 'test'],
					[Token::END, '}}'],
				],
			]
		]);
	}

	public function testAssortedTokenLexing()
	{
		$this->runTokenTestLoop([
			[
				'test' => 'Lexing an assortment of lexable tokens',
				'code' => [
					'{{a + b * \'string\', 1024--1 && | (-3.14 : abc[123])}}',
				],
				'expects' => [
					[Token::BEGIN, '{{'],
					[Token::IDENTIFIER, 'a'],
					[Token::OPERATOR, '+'],
					[Token::IDENTIFIER, 'b'],
					[Token::OPERATOR, '*'],
					[Token::STRING, 'string'],
					[Token::SEPARATOR, ','],
					[Token::NUMBER, '1024'],
					[Token::OPERATOR, '--'],
					[Token::NUMBER, '1'],
					[Token::OPERATOR, '&&'],
					[Token::SEPARATOR, '|'],
					[Token::DELIMITER, '('],
					[Token::NUMBER, '-3.14'],
					[Token::SEPARATOR, ':'],
					[Token::IDENTIFIER, 'abc'],
					[Token::DELIMITER, '['],
					[Token::NUMBER, '123'],
					[Token::DELIMITER, ']'],
					[Token::DELIMITER, ')'],
					[Token::END, '}}'],
				],
			]
		]);
	}

	public function testOperatorLexing()
	{
		$this->runTokenTestLoop([
			[
				'test' => 'Lexing null-coalescing operators',
				'code' => [
					'{{??}}',
				],
				'expects' => [
					[Token::BEGIN],
					[Token::OPERATOR, '??'],
					[Token::END],
				],
			],
			[
				'test' => 'Lexing ternary operators',
				'code' => [
					'{{?:}}',
				],
				'expects' => [
					[Token::BEGIN],
					[Token::OPERATOR, '?:'],
					[Token::END],
				],
			],
			[
				'test' => 'Lexing and/or operators',
				'code' => [
					'{{and or}}',
				],
				'expects' => [
					[Token::BEGIN],
					[Token::OPERATOR, 'and'],
					[Token::OPERATOR, 'or'],
					[Token::END],
				],
			],
			[
				'test' => 'Lexing comparison operators',
				'code' => [
					'{{> <= ==}}',
				],
				'expects' => [
					[Token::BEGIN],
					[Token::OPERATOR, '>'],
					[Token::OPERATOR, '<='],
					[Token::OPERATOR, '=='],
					[Token::END],
				],
			],
		]);
	}

	public function testLexerFile()
	{
		$content = file_get_contents(__DIR__.'/fixtures/test.phtml');
		$this->runTokenTestLoop([
			[
				'test' => 'Lexing test.phtml file',
				'code' => $content,
				'expects' => [
					[Token::RAW],
					[Token::BEGIN, '{{'],
					[Token::IDENTIFIER, 'lang'],
					[Token::END, '}}'],
					[Token::RAW],
				],
			],
		]);
	}

	protected function runTokenTestLoop(array $vectors)
	{
		$lexer = $this->getLexerObject();
		foreach ($vectors as $vector) {
			$codes = is_array($vector['code']) ? $vector['code'] : [$vector['code']];
			foreach ($codes as $code) {
				$tokens = $lexer->tokenize($code);

				if (isset($vector['expects'])) {
					foreach ($vector['expects'] as $expect) {
						$token = $tokens->next();
						if (count($expect) > 1) {
							$success = $token->is($expect[0], $expect[1]);
							$expected = $expect[1];
						} else {
							$success = $token->is($expect[0]);
							$expected = Token::getTypeFullName($expect[0]);
						}
						$this->assertTrue($success, $vector['test']."\nExpected: $expected\nFound: ".$token);
					}

					if (count($vector['expects']) >= count($tokens)) {
						$this->assertEquals($tokens->current->getType(), Token::EOF, $vector['test']);
					}
				}
			}
		}
	}

	protected function getLexerObject(): Lexer
	{
		return new Lexer(m::mock(Engine::class));
	}
}
