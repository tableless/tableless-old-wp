<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Compress_No_Client_Test extends Tiny_TestCase {
	public static function set_up_before_class() {
		Tiny_PHP::$client_supported = false;
		Tiny_PHP::$fopen_available = false;
	}

	public function test_should_throw_error_when_curl_and_fopen_unavailable() {
		$this->setExpectedException( 'Tiny_Exception' );
		Tiny_Compress::create( 'api1234' );
	}
}
