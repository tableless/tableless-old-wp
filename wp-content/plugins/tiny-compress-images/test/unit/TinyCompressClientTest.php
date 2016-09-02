<?php

require_once dirname( __FILE__ ) . '/TinyCompressSharedTestCase.php';

class Tiny_Compress_Client_Test extends Tiny_Compress_Shared_TestCase {
	public static function set_up_before_class() {
		Tiny_PHP::$client_supported = true;
	}

	public function set_up() {
		parent::set_up();
		$this->client = new MockTinifyClient();
		Tinify\Tinify::setClient( $this->client );
	}

	protected function register( $method, $url, $details ) {
		$this->client->register( $method, $url, $details );
	}

	public function test_should_return_client_compressor() {
		$this->assertInstanceOf( 'Tiny_Compress_Client', $this->compressor );
	}

	public function test_can_create_key_should_return_true() {
		$this->assertSame( true, $this->compressor->can_create_key() );
	}

	public function test_create_key_should_set_api_key() {
		$this->register( 'POST', '/keys', array(
			'status' => 202,
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body' => json_encode(array(
				'key' => 'newkey123',
			)),
		));

		$this->compressor->create_key( 'john@example.com', array(
			'name' => 'John Doe',
		));

		$this->assertEquals(
			'newkey123',
			$this->compressor->get_key()
		);
	}
}
