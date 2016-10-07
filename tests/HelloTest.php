<?php

/**
 * @coversDefaultClass \TestNameSpace\Hello
 */
class HelloTest extends \PHPUnit_Framework_TestCase
{
	protected $hello;
	
	public function setUp()
	{
		$this->hello = new \TestNameSpace\Hello();
	}
	
	/**
	 * @covers ::world
	 */
	public function testWorld()
	{
		$this->assertSame('world', $this->hello->world());
	}
}