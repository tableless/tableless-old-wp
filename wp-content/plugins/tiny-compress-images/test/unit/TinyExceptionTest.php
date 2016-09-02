<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Exception_Test extends Tiny_TestCase {
	public function test_constructor_creates_exception_with_message() {
		$err = new Tiny_Exception( 'Message' );
		$this->assertInstanceOf( 'Tiny_Exception', $err );
	}

	public function test_constructor_creates_exception_with_message_and_error() {
		$err = new Tiny_Exception( 'Message', 'ErrorType' );
		$this->assertInstanceOf( 'Tiny_Exception', $err );
	}

	public function test_constructor_throws_if_message_is_not_a_string() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new Tiny_Exception( 404, 'ErrorType' );
	}

	public function test_constructor_throws_if_error_is_not_a_string() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new Tiny_Exception( 'Message', new Exception( 'err' ) );
	}
}
