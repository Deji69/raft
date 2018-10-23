<?php
namespace Raft\Lexer;

trait TokenOperator
{
	protected static $operatorTable = [
		'=' => [		// =
			'=',		// ==
		],
		'+' => [		// +
			'=',		// +=
			'+',		// ++
		],
		'-' => [		// -
			'=',		// -=
			'-',		// --
		],
		'*' => [		// *
			'=',		// *=
		],
		'/' => [		// /
			'=',		// /=
		],
		'%' => [		// %
			'=',		// %=
		],
		'~' => [		// ~
			'=',		// ~=
		],
		'^' => [		// ^
			'=',		// ^=
		],
		'<' => [		// <
			'=',		// <=
			'<' => [	// <<
				'=',	// <<=
			],
		],
		'>' => [		// >
			'=',		// >=
			'>' => [	// >>
				'=',	// >>=
			],
		],
		'!' => [		// !
			'=',		// !=
		],
		'&' => [		// &
			'&',		// &&
			'=',		// &=
		],
		'|' => [		// |
			'|',		// ||
			'=',		// |=
		],
		'?' => [		// ?
			'?',		// ??
		],
	];

	public function lexOperator($src, $pos) {
		$table = static::$operatorTable;
		$begin = $pos;
		while (true) {
			if (isset($table[$src[$pos]])) {
				$table = $table[$src[$pos]];
				++$pos;
				continue;
			}
			if (in_array($src[$pos], $table)) {
				return ++$pos;
			}
			break;
		}
		return $begin != $pos ? $pos : false;
	}
}
