<?php
namespace Raft;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Raft\Engine;

class EngineTest extends TestCase
{
	public function tearDown()
	{
		m::close();
	}

	public function testDependencyResolution()
	{
	}
}
