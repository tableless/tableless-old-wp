<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Compress_No_Curl_Test extends TinyTestCase {
    protected $mock;

    public function setUp() {
        parent::setUp();
        $this->php_mock = \Mockery::mock('alias:Tiny_PHP');
        $this->php_mock->shouldReceive('is_curl_available')->andReturn(false);
    }

    public function testShouldReturnFopenCompressorIfCurlUnavailable() {
        $this->php_mock->shouldReceive('is_fopen_available')->andReturn(true);
        $compressor = Tiny_Compress::get_compressor('api1234');
        $this->assertInstanceOf('Tiny_Compress_Fopen', $compressor);
    }

    /**
     * @expectedException Tiny_Exception
     */
    public function testShouldThrowErrorWhenCurlAndFopenUnavailable() {
        $this->php_mock->shouldReceive('is_fopen_available')->andReturn(false);
        $compressor = Tiny_Compress::get_compressor('api1234');
        $this->assertInstanceOf('Tiny_Compress', $compressor);
    }
}
