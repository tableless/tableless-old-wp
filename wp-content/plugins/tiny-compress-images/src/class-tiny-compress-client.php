<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2016 Voormedia B.V.
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the Free
* Software Foundation; either version 2 of the License, or (at your option)
* any later version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
* FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
* more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc., 51
* Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

if ( ! defined( '\Tinify\VERSION' ) ) {
	/* Load vendored client if it is not yet loaded. */
	require_once dirname( __FILE__ ) . '/vendor/tinify/Tinify/Exception.php';
	require_once dirname( __FILE__ ) . '/vendor/tinify/Tinify/ResultMeta.php';
	require_once dirname( __FILE__ ) . '/vendor/tinify/Tinify/Result.php';
	require_once dirname( __FILE__ ) . '/vendor/tinify/Tinify/Source.php';
	require_once dirname( __FILE__ ) . '/vendor/tinify/Tinify/Client.php';
	require_once dirname( __FILE__ ) . '/vendor/tinify/Tinify.php';
}

class Tiny_Compress_Client extends Tiny_Compress {
	private $last_error_code = 0;
	private $last_message = '';
	private $proxy;

	protected function __construct( $api_key, $after_compress_callback ) {
		parent::__construct( $after_compress_callback );

		$this->proxy = new WP_HTTP_Proxy();

		\Tinify\setAppIdentifier( self::identifier() );
		\Tinify\setKey( $api_key );
	}

	public function can_create_key() {
		return true;
	}

	public function get_compression_count() {
		return \Tinify\getCompressionCount();
	}

	public function get_key() {
		return \Tinify\getKey();
	}

	public function limit_reached() {
		return 429 == $this->last_error_code;
	}

	protected function validate() {
		try {
			$this->last_error_code = 0;
			$this->set_request_options( \Tinify\Tinify::getClient() );

			\Tinify\Tinify::getClient()->request( 'post', '/shrink' );
			return true;

		} catch (\Tinify\Exception $err) {
			$this->last_error_code = $err->status;

			if ( 429 == $err->status || 400 == $err->status ) {
				return true;
			}

			throw new Tiny_Exception(
				$err->getMessage(),
				get_class( $err ),
				$err->status
			);
		}
	}

	protected function compress( $input, $resize_opts, $preserve_opts ) {
		try {
			$this->last_error_code = 0;
			$this->set_request_options( \Tinify\Tinify::getClient() );

			$source = \Tinify\fromBuffer( $input );

			if ( $resize_opts ) {
				$source = $source->resize( $resize_opts );
			}

			if ( $preserve_opts ) {
				$source = $source->preserve( $preserve_opts );
			}

			$result = $source->result();

			$meta = array(
				'input' => array(
					'size' => strlen( $input ),
					'type' => $result->mediaType(),
				),
				'output' => array(
					'size' => $result->size(),
					'type' => $result->mediaType(),
					'width' => $result->width(),
					'height' => $result->height(),
					'ratio' => round( $result->size() / strlen( $input ), 4 ),
				),
			);

			$buffer = $result->toBuffer();
			return array( $buffer, $meta );

		} catch (\Tinify\Exception $err) {
			$this->last_error_code = $err->status;

			throw new Tiny_Exception(
				$err->getMessage(),
				get_class( $err ),
				$err->status
			);
		}
	}

	public function create_key( $email, $options ) {
		try {
			$this->last_error_code = 0;
			$this->set_request_options(
				\Tinify\Tinify::getClient( \Tinify\Tinify::ANONYMOUS )
			);

			\Tinify\createKey( $email, $options );
		} catch (\Tinify\Exception $err) {
			$this->last_error_code = $err->status;

			throw new Tiny_Exception(
				$err->getMessage(),
				get_class( $err ),
				$err->status
			);
		}
	}

	private function set_request_options( $client ) {
		/* The client does not let us override cURL properties yet, so we have
           to use a reflection property. */
		$property = new ReflectionProperty( $client, 'options' );
		$property->setAccessible( true );
		$options = $property->getValue( $client );

		if ( TINY_DEBUG ) {
			$file = fopen( dirname( __FILE__ ) . '/curl.log', 'w' );
			if ( is_resource( $file ) ) {
				$options[ CURLOPT_VERBOSE ] = true;
				$options[ CURLOPT_STDERR ] = $file;
			}
		}

		if ( $this->proxy->is_enabled() && $this->proxy->send_through_proxy( $url ) ) {
			$options[ CURLOPT_PROXYTYPE ] = CURLPROXY_HTTP;
			$options[ CURLOPT_PROXY ] = $this->proxy->host();
			$options[ CURLOPT_PROXYPORT ] = $this->proxy->port();

			if ( $this->proxy->use_authentication() ) {
				$options[ CURLOPT_PROXYAUTH ] = CURLAUTH_ANY;
				$options[ CURLOPT_PROXYUSERPWD ] = $this->proxy->authentication();
			}
		}
	}
}
