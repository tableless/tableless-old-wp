<?php

require_once dirname( __FILE__ ) . '/../helpers/mock-http-stream-wrapper.php';
require_once dirname( __FILE__ ) . '/../helpers/mock-tinify-client.php';
require_once dirname( __FILE__ ) . '/../helpers/wordpress.php';
require_once dirname( __FILE__ ) . '/../../src/config/tiny-config.php';
require_once 'vendor/autoload.php';

use org\bovigo\vfs\vfsStream;

function plugin_autoloader( $class ) {
	$file = dirname( __FILE__ ) . '/../../src/class-' .
		str_replace( '_', '-', strtolower( $class ) ) . '.php';

	if ( file_exists( $file ) ) {
		include $file;
	} else {
		spl_autoload( $class );
	}
}

spl_autoload_register( 'plugin_autoloader' );

class Tiny_PHP {
	public static $fopen_available = true;
	public static $client_supported = true;

	public static function fopen_available() {
		return self::$fopen_available;
	}

	public static function client_supported() {
		return self::$client_supported;
	}
}

abstract class Tiny_TestCase extends PHPUnit_Framework_TestCase {
	protected $wp;
	protected $vfs;

	// @codingStandardsIgnoreStart
	public static function setUpBeforeClass() {
		static::set_up_before_class();
	}

	public static function tearDownAfterClass() {
		static::tear_down_after_class();
	}

	protected function setUp() {
		$this->set_up();
	}

	protected function tearDown() {
		$this->tear_down();
	}

	protected function assertBetween($lower_bound, $upper_bound, $actual, $message = '') {
		$this->assertGreaterThanOrEqual( $lower_bound, $actual, $message );
		$this->assertLessThanOrEqual( $upper_bound, $actual, $message );
	}

	protected function assertEqualWithinDelta($expected, $actual, $delta, $message = '') {
		$this->assertGreaterThanOrEqual( $expected - $delta, $actual, $message );
		$this->assertLessThanOrEqual( $expected + $delta, $actual, $message );
	}
	// @codingStandardsIgnoreEnd

	protected function json( $file_name ) {
		return json_decode(
			file_get_contents(
				dirname( __FILE__ ) . '/../fixtures/json/' . $file_name . '.json'
			),
			true
		);
	}

	public static function set_up_before_class() {
	}

	public static function tear_down_after_class() {
		Tiny_PHP::$client_supported = true;
		Tiny_PHP::$fopen_available = true;
	}

	protected function set_up() {
		$this->vfs = vfsStream::setup();
		$this->wp = new WordPressStubs( $this->vfs );
	}

	protected function tear_down() {
	}
}
