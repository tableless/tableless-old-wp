<?php

class MockHttpStreamWrapper implements IteratorAggregate, ArrayAccess, Countable {
	public static $handlers = array();

	public $context;
	public $position = 0;

	protected $data = array();

	public static function clear() {
		self::$handlers = array();
	}

	public static function register( $method, $url, $handler ) {
		$key = self::get_key( $method, $url );
		self::$handlers[ $key ] = $handler;
	}

	private static function get_key( $method, $url ) {
		return strtoupper( $method ) . ' ' . $url;
	}

	/* IteratorAggregate */

	public function getIterator() {
		return new ArrayIterator( $this->data );
	}

	/* ArrayAccess */

	public function offsetExists( $offset ) {
		return array_key_exists( $offset, $this->data );
	}

	public function offsetGet( $offset ) {
		return $this->data[ $offset ];
	}

	public function offsetSet( $offset, $value ) {
		$this->data[ $offset ] = $value;
	}

	public function offsetUnset( $offset ) {
		unset( $this->data[ $offset ] );
	}

	/* Countable */
	public function count() {
		return count( $this->data );
	}

	/* StreamWrapper */
	public function stream_open( $path, $mode, $options, &$opened_path ) {
		$context = stream_context_get_options( $this->context );
		$path = str_replace( 'https://api.tinify.com', '', $path );
		$key = self::get_key( $context['http']['method'], $path );
		if ( isset( self::$handlers[ $key ] ) ) {
			$handler = self::$handlers[ $key ];

			$status = 'HTTP/1.1 ' . $handler['status'];
			$body = isset( $handler['body'] ) ? $handler['body'] : '';
			$headers = isset( $handler['headers'] ) ? $handler['headers'] : array();

			$this->mocked_body = $body;
			$this->mocked_status = $status;

			array_push( $this->data, $status );
			foreach ( $headers as $header => $value ) {
				array_push( $this->data, $header . ': ' . $value );
			}
		} else {
			throw new Exception( 'No handler for ' . $key );
		}
		return true;
	}

	public function stream_read( $count ) {
		if ( $this->position > strlen( $this->mocked_body ) ) {
			return false;
		}
		$result = substr( $this->mocked_body, $this->position, $count );
		$this->position += $count;
		return $result;
	}

	public function stream_eof() {
		return $this->position >= strlen( $this->mocked_body );
	}

	public function stream_stat() {
		return array( 'wrapper_data' => array( 'test' ) );
	}

	public function stream_tell() {
		return $this->position;
	}
}
