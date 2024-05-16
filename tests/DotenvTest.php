<?php

error_reporting(E_ALL);

class DotenvTest extends PHPUnit\Framework\TestCase {
	public function testReadFile() {
		$this->assertTrue(\AKEB\Dotenv::load(__DIR__,'.env'));
		$this->assertEquals($_ENV['PARAM1'], 'value1');
		$this->assertEquals($_ENV['PARAM2'], 'value2');
		$this->assertEquals($_ENV['PARAM3'], 'value3 text');
	}
}