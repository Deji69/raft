<?php
namespace Raft\Tests;

use Mockery as m;
use Raft\Source;
use PHPUnit\Framework\TestCase;

class SourceTest extends TestCase
{
	protected static $testLinesCode = <<<EOD
This line has 24 columns
This line ɥás… § unicode
⅒

Line 5
EOD;

	public function tearDown()
	{
		m::close();
	}

	public function testGetLinesAndColumns()
	{
		$source = $this->getSource();

		// note: column is 1-indexed while offset is 0-indexed
		// line 2 has no MB chars and is 25 chars long (including newline)
		$offset = $source->getOffsetForLine(2);
		$this->assertEquals(25, $offset, '->getOffsetForLine() returns the number of bytes up to the line');

		$line = $source->getLineForOffset($offset);
		$this->assertEquals(2, $line, '->getLineForOffset() returns the line number for the code offset');

		$offset = $source->getOffsetForLine(3);
		$this->assertEquals(55, $offset, '->getOffsetForLine() returns the number of bytes including MB characters up to the line');

		$col = $source->getColumnForOffset(54);
		$this->assertEquals(25, $col, '->getColumnForOffset() returns the columns into a line the byte offset is');

		$offset = $source->getOffsetForLine(4) - 1;
		$col = $source->getColumnForOffset($offset);
		$this->assertEquals(2, $col, '->getColumnForOffset() works with double width characters');
	}

	protected function getSource()
	{
		return new Source(self::$testLinesCode, 'test');
	}
}
