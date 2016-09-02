<?php

require dirname( __FILE__ ) . '/../unit/TinyTestCase.php';
require dirname( __FILE__ ) . '/../helpers/setup.php';

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\RemoteWebDriver;

abstract class IntegrationTestCase extends Tiny_TestCase {
	protected static $driver;
	protected static $db;

	public static function set_up_before_class() {
		self::$driver = RemoteWebDriver::createBySessionId(
			$GLOBALS['global_session_id'],
			$GLOBALS['global_webdriver_host']
		);

		self::$db = new mysqli( getenv( 'HOST' ), 'root',
			getenv( 'MYSQL_PWD' ),
			getenv( 'WORDPRESS_DATABASE' )
		);
	}

	protected function visit( $path ) {
		self::$driver->get( wordpress( $path ) );
	}

	protected function refresh() {
		self::$driver->navigate()->refresh();
	}

	protected function find( $selector, $base = null ) {
		if ( ! $base ) {
			$base = self::$driver;
		}

		return $base->findElement(
			WebDriverBy::cssSelector( $selector )
		);
	}

	protected function find_all( $selector, $base = null ) {
		if ( ! $base ) {
			$base = self::$driver;
		}

		return $base->findElements(
			WebDriverBy::cssSelector( $selector )
		);
	}

	protected function find_link( $text ) {
		return self::$driver->findElement(
			WebDriverBy::partialLinkText( $text )
		);
	}

	protected function find_button( $text ) {
		return self::$driver->findElement(
			WebDriverBy::cssSelector( "input[value='{$text}']" )
		);
	}

	protected function wait_for_text( $selector, $text ) {
		self::$driver->wait( 2 )->until(
			WebDriverExpectedCondition::textToBePresentInElement(
				WebDriverBy::cssSelector( $selector ), $text
			)
		);
	}

	protected function wait_for_text_disappearance( $selector, $text ) {
		self::$driver->wait( 2 )->until(
			WebDriverExpectedCondition::invisibilityOfElementWithText(
				WebDriverBy::cssSelector( $selector ), $text
			)
		);
	}

	protected function has_postbox_container() {
		return wordpress_version() >= 35;
	}

	protected function postbox_dimension_selector() {
		$version = wordpress_version();
		if ( $version < 37 ) {
			return 'div.misc-pub-section:nth-child(5)';
		} elseif ( $version == 37 ) {
			return 'div.misc-pub-section:nth-child(6)';
		} else {
			return 'div.misc-pub-dimensions';
		}
	}

	protected function upload_media( $path ) {
		$this->visit( '/wp-admin/media-new.php?browser-uploader&flash=0' );

		$links = $this->find_all( 'p.upload-flash-bypass a' );
		if ( count( $links ) > 0 && $links[0]->isDisplayed() ) {
			$links[0]->click();
		}

		$this->find( 'input[name=async-upload]' )->sendKeys( $path );
		$this->find( 'input[type=submit]' )->click();

		$this->wait_for_text( 'div.wrap h1, div.wrap h2', 'Media Library' );
	}

	protected function set_api_key( $api_key, $wait = true ) {
		$this->set_option( 'tinypng_api_key', $api_key );
	}

	protected function enable_compression_sizes( $sizes ) {
		$value = array( '_tiny_dummy' => 'on' );
		foreach ( $sizes as $size ) {
			$value[ $size ] = 'on';
		}
		$this->set_option( 'tinypng_sizes', serialize( $value ) );
	}

	protected function enable_preserve( $keys ) {
		$value = array();
		foreach ( $keys as $key ) {
			$value[ $key ] = 'on';
		}
		$this->set_option( 'tinypng_preserve_data', serialize( $value ) );
	}

	protected function disable_preserve() {
		$this->unset_option( 'tinypng_preserve_data' );
	}

	protected function enable_resize( $options ) {
		$value = array( 'enabled' => 'on' );
		foreach ( $options as $option => $val ) {
			$value[ $option ] = $val;
		}
		$this->set_option( 'tinypng_resize_original', serialize( $value ) );
	}

	protected function disable_resize() {
		$this->unset_option( 'tinypng_resize_original' );
	}

	protected function set_option( $name, $value ) {
		$this->unset_option( $name );

		$query = self::$db->prepare(
			'INSERT INTO wp_options (option_name, option_value) VALUES (?, ?)'
		);

		$query->bind_param( 'ss', $name, $value );
		$query->execute();
	}

	protected function unset_option( $name ) {
		$query = self::$db->prepare(
			'DELETE FROM wp_options WHERE option_name = ?'
		);

		$query->bind_param( 's', $name );
		$query->execute();
	}
}
