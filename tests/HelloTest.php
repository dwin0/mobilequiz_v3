<?php

include_once __DIR__.'/../Hello.php';

/**
 * @coversDefaultClass \Hello
 */
class HelloTest extends \PHPUnit_Framework_TestCase
{
	protected $hello;
	
	public function setUp()
	{
		$this->hello = new Hello();
	}
	
	/**
	 * @covers ::world
	 */
	public function testWorld()
	{
		$this->assertSame('world', $this->hello->world());
	}
}