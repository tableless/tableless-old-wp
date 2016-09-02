<?php

class MockTinifyClient extends Tinify\Client {
	function __construct( $key = null, $appIdentifier = null ) {
		parent::__construct( $key, $appIdentifier );
		$this->handlers = array();
	}

	public function request( $method, $url, $body = null, $header = array() ) {
		$url = str_replace( 'https://api.tinify.com', '', $url );
		$key = $this->get_key( $method, $url );
		if ( isset( $this->handlers[ $key ] ) ) {
			$handler = $this->handlers[ $key ];

			$status = $handler['status'];
			$body = isset( $handler['body'] ) ? $handler['body'] : '';
			$headers = isset( $handler['headers'] ) ? $handler['headers'] : array();

			if ( isset( $headers['compression-count'] ) ) {
				\Tinify\Tinify::setCompressionCount(
					intval( $headers['compression-count'] )
				);
			}

			$isError = $status <= 199 || $status >= 300;
			$isJson = true;

			if ( $isJson || $isError ) {
				$body = json_decode( $body );
			}

			if ( $isError ) {
				throw \Tinify\Exception::create( $body->message, $body->error, $status );
			}

			return (object) array( 'body' => $body, 'headers' => $headers );
		} else {
			throw new Exception( 'No handler for ' . $key );
		}
	}

	public function register( $method, $url, $handler ) {
		$key = $this->get_key( $method, $url );
		$this->handlers[ $key ] = $handler;
	}

	private function get_key( $method, $url ) {
		return strtoupper( $method ) . ' ' . $url;
	}
}
