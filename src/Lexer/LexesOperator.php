<?php
namespace Raft\Lexer;

trait LexesOperator
{
	protected static $operatorTable = [
		'=' => [		// =
			'=',		// ==
		],
		'+' => [		// +
			//'=',		// +=
			'+',		// ++
		],
		'-' => [		// -
			//'=',		// -=
			'-',		// --
		],
		'*' => [		// *
			'*' => [	// **
				//'=',	// **=
			],
			//'=',		// *=
		],
		'/' => [		// /
			//'=',		// /=
		],
		'%' => [		// %
			//'=',		// %=
		],
		/*'~' => [		// ~
			'=',		// ~=
		],*/
		/*'^' => [		// ^
			'=',		// ^=
		],*/
		'<' => [		// <
			'=',		// <=
			/*'<' => [	// <<
				'=',	// <<=
			],*/
		],
		'>' => [		// >
			'=',		// >=
			/*'>' => [	// >>
				'=',	// >>=
			],*/
		],
		'!' => [		// !
			'=',		// !=
		],
		/*'&' => [		// &
			'&',		// &&
			'=',		// &=
		],
		'|' => [		// |
			'|',		// ||
			'=',		// |=
		],*/
		'&&',			// &&
		'||',			// ||
		'??',			// ??
		'?:',			// ?:
		'and',			// and
		'or',			// or
	];

	public function lexOperator($src, $pos) {
		$table = static::$operatorTable;
		$begin = $pos;
		do {
			for ($i = 0; isset($table[$i]); ++$i) {
				$l = strlen($table[$i]);
				if (substr($src, $pos, $l) == $table[$i]) {
					return $pos + $l;
				}
			}

			if (isset($table[$src[$pos]])) {
				$table = $table[$src[$pos]];
				++$pos;
				continue;
			}
			if (in_array($src[$pos], $table)) {
				return ++$pos;
			}
			break;
		} while (is_array($table) && !empty($table));
		return $begin != $pos ? $pos : false;
	}
}
