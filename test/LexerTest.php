<?php
namespace Raft\Tests;

use Mockery as m;
use Raft\Token;
use Raft\Lexer;
use Raft\Engine;
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
		$contents = file_get_contents(__DIR__.'/fixtures/test.phtml');
		$tokens = $this->getLexerObject()->tokenize($contents);
		print_r($tokens);
	}

	protected function runTokenTestLoop(array $vectors)
	{
		$lexer = $this->getLexerObject();
		foreach ($vectors as $vector) {
			$codes = is_array($vector['code']) ? $vector['code'] : [$vector['code']];
			foreach ($codes as $code) {
				$tokens = $lexer->tokenize($code);
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

				$this->assertEquals($tokens->current->getType(), Token::EOF, $vector['test']);
			}
		}
	}

	protected function getLexerObject(): Lexer
	{
		return new Lexer(m::mock(Engine::class));
	}
}
