<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Compress_Test extends TinyTestCase {
    protected $php_mock;

    public function setUp() {
        parent::setUp();
        $this->php_mock = \Mockery::mock('alias:Tiny_PHP');
        $this->php_mock->shouldReceive('is_curl_available')->andReturn(true);
    }

    public function testShouldReturnCompressor() {
        $compressor = Tiny_Compress::get_compressor('api1234');
        $this->assertInstanceOf('Tiny_Compress', $compressor);
    }

    public function testShouldReturnCurlCompressorByDefault() {
        $compressor = Tiny_Compress::get_compressor('api1234');
        $this->assertInstanceOf('Tiny_Compress_Curl', $compressor);
    }
}
