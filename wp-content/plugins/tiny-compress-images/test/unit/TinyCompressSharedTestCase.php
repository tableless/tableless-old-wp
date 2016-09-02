<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

abstract class Tiny_Compress_Shared_TestCase extends Tiny_TestCase {
	public function set_up() {
		parent::set_up();
		$this->after_compress_called = false;
		$after_compress_called = &$this->after_compress_called;
		$callback = function( $compressor ) use ( &$after_compress_called ) {
			$after_compress_called = true;
		};
		$this->compressor = Tiny_Compress::create( 'api1234', $callback );
	}

	protected abstract function register( $method, $url, $details );

	public function test_should_return_client_compressor() {
		$this->assertInstanceOf( 'Tiny_Compress', $this->compressor );
	}

	public function test_get_key_should_return_key() {
		$this->assertSame( 'api1234', $this->compressor->get_key() );
	}

	public function test_get_status_should_return_success_status() {
		$this->register( 'POST', '/shrink', array(
			'status' => 400,
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body' => json_encode(array(
				'error' => 'Input missing',
				'message' => 'No file provided',
			)),
		));

		$this->assertEquals(
			(object) array(
				'ok' => true,
				'message' => null,
			),
			$this->compressor->get_status()
		);

		$this->assertEquals(
			false,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);
	}

	public function test_get_status_should_return_limit_reached_status() {
		$this->register( 'POST', '/shrink', array(
			'status' => 429,
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body' => json_encode(array(
				'error' => 'Too many requests',
				'message' => 'Your monthly limit has been exceeded',
			)),
		));

		$this->assertEquals(
			(object) array(
				'ok' => true,
				'message' => null,
			),
			$this->compressor->get_status()
		);

		$this->assertEquals(
			true,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);
	}

	public function test_get_status_should_return_unauthorized_status() {
		$this->register( 'POST', '/shrink', array(
			'status' => 401,
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body' => json_encode(array(
				'error' => 'Unauthorized',
				'message' => 'Credentials are invalid',
			)),
		));

		$this->assertEquals(
			(object) array(
				'ok' => false,
				'message' => 'The key that you have entered is not valid',
			),
			$this->compressor->get_status()
		);

		$this->assertEquals(
			false,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);
	}

	public function test_compress_file_should_save_compressed_file() {
		$this->register( 'POST', '/shrink', array(
			'status' => 201,
			'headers' => array(
				'location' => 'https://api.tinify.com/output/compressed.png',
				'content-type' => 'application/json',
			),
			'body' => '{}',
		));

		$handler = array(
			'status' => 200,
			'headers' => array(
				'content-type' => 'image/png',
				'content-length' => 9,
				'image-width' => 10,
				'image-height' => 15,
			),
			'body' => 'optimized',
		);

		$this->register( 'GET', '/output/compressed.png', $handler );
		$this->register( 'POST', '/output/compressed.png', $handler );

		file_put_contents( $this->vfs->url() . '/image.png', 'unoptimized' );

		$this->assertEquals(
			array(
				'input' => array(
					'size' => 11,
					'type' => 'image/png',
				),
				'output' => array(
					'size' => 9,
					'type' => 'image/png',
					'width' => 10,
					'height' => 15,
					'ratio' => round( 9 / 11, 4 ),
				),
			),
			$this->compressor->compress_file( $this->vfs->url() . '/image.png' )
		);

		$this->assertEquals(
			false,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);
	}

	public function test_compress_file_should_save_resized_file() {
		$this->register( 'POST', '/shrink', array(
			'status' => 201,
			'headers' => array(
				'location' => 'https://api.tinify.com/output/compressed.png',
				'content-type' => 'application/json',
			),
			'body' => '{}',
		));

		$handler = array(
			'status' => 200,
			'headers' => array(
				'content-type' => 'image/png',
				'content-length' => 5,
				'image-width' => 6,
				'image-height' => 9,
			),
			'body' => 'small',
		);

		$this->register( 'GET', '/output/compressed.png', $handler );
		$this->register( 'POST', '/output/compressed.png', $handler );

		$img = file_get_contents( 'test/fixtures/input-example.jpg' );
		file_put_contents( $this->vfs->url() . '/image.png', $img );

		$resize = array(
			'width' => 9,
			'method' => 'fit',
		);

		$this->assertEquals(
			array(
				'input' => array(
					'size' => 641206,
					'type' => 'image/png',
				),
				'output' => array(
					'size' => 5,
					'type' => 'image/png',
					'width' => 6,
					'height' => 9,
					'ratio' => round( 5 / 641206, 4 ),
					'resized' => true,
				),
			),
			$this->compressor->compress_file( $this->vfs->url() . '/image.png', $resize )
		);

		$this->assertEquals(
			false,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);
	}

	public function test_compress_file_should_return_unauthorized_status() {
		$this->register( 'POST', '/shrink', array(
			'status' => 401,
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body' => json_encode(array(
				'error' => 'Unauthorized',
				'message' => 'Credentials are invalid',
			)),
		));

		file_put_contents( $this->vfs->url() . '/image.png', 'unoptimized' );

		$exception = null;
		try {
			$this->compressor->compress_file( $this->vfs->url() . '/image.png' );
		} catch (Exception $err) {
			$exception = $err;
		}

		$this->assertEquals(
			false,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);

		$this->setExpectedException( 'Tiny_Exception' );
		throw $exception;
	}

	public function test_get_compression_count_should_return_null_before_compresion() {
		$this->assertSame( null, $this->compressor->get_compression_count() );
	}

	public function test_get_compression_count_should_return_count() {
		$this->register( 'POST', '/shrink', array(
			'status' => 201,
			'headers' => array(
				'location' => 'https://api.tinify.com/output/compressed.png',
				'content-type' => 'application/json',
				'compression-count' => 12,
			),
			'body' => '{}',
		));

		$handler = array(
			'status' => 200,
			'headers' => array(
				'content-type' => 'image/png',
				'content-length' => 9,
				'image-width' => 10,
				'image-height' => 15,
				'compression-count' => 12,
			),
			'body' => 'optimized',
		);

		$this->register( 'GET', '/output/compressed.png', $handler );
		$this->register( 'POST', '/output/compressed.png', $handler );

		file_put_contents( $this->vfs->url() . '/image.png', 'unoptimized' );
		$this->compressor->compress_file( $this->vfs->url() . '/image.png' );

		$this->assertSame( 12, $this->compressor->get_compression_count() );
	}
}
