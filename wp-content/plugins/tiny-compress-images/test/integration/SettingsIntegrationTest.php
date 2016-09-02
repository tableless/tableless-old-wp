<?php

require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';

class SettingsIntegrationTest extends IntegrationTestCase {
	public function set_up() {
		parent::set_up();
		$this->visit( '/wp-admin/options-media.php' );
	}

	public function tear_down() {
		parent::tear_down();
		clear_settings();
		clear_uploads();
	}

	protected function get_enabled_sizes() {
		return array_map( function( $checkbox ) {
			return $checkbox->getAttribute( 'name' );
		}, $this->find_all( 'input[type=checkbox][checked][name^=tinypng_sizes]' ) );
	}

	public function test_settings_should_contain_title() {
		$headings = array_map( function( $heading ) {
			return $heading->getText();
		}, $this->find_all( 'h2, h3' ) );

		$this->assertContains( 'JPEG and PNG optimization', $headings );
	}

	public function test_settings_should_show_notice_if_key_is_missing() {
		$this->assertStringEndsWith(
			'options-media.php#tiny-compress-images',
			$this->find( '.error a' )->getAttribute( 'href' )
		);
	}

	public function test_settings_should_not_show_notice_if_key_is_set() {
		$this->set_api_key( 'PNG123' );
		$this->refresh();

		$this->assertEquals( 0, count( $this->find_all( '.error a' ) ) );
	}

	public function test_settings_should_store_valid_api_key() {
		$this->find( '#tinypng_api_key' )->sendKeys( 'PNG123' );
		$this->find( 'button[data-tiny-action=update-key]' )->click();

		$this->wait_for_text(
			'div.tiny-account-status p.status',
			'Your account is connected'
		);

		$this->refresh();

		$this->assertEquals(
			'Your account is connected',
			$this->find( 'div.tiny-account-status p.status' )->getText()
		);
	}

	public function test_settings_should_not_store_invalid_api_key() {
		$this->find( '#tinypng_api_key' )->sendKeys( 'INVALID123' );
		$this->find( 'button[data-tiny-action=update-key]' )->click();

		$this->wait_for_text(
			'div.tiny-account-status div.update p.message',
			'The key that you have entered is not valid'
		);

		$this->refresh();

		$this->assertEquals(
			'Register new account',
			$this->find( 'div.tiny-account-status h4' )->getText()
		);
	}

	public function test_settings_should_allow_changing_api_key() {
		$this->find( '#tinypng_api_key' )->sendKeys( 'PNG123' );
		$this->find( 'button[data-tiny-action=update-key]' )->click();

		$this->wait_for_text(
			'div.tiny-account-status p.status',
			'Your account is connected'
		);

		$this->find_link( 'Change API key' )->click();

		$this->find( '#tinypng_api_key' )->sendKeys( 'JPG123' );
		$this->find( 'button[data-tiny-action=update-key]' )->click();

		$this->wait_for_text(
			'div.tiny-account-status p.status',
			'Your account is connected'
		);
	}

	public function test_settings_should_pre_fill_registration_form() {
		$this->assertEquals(
			'',
			$this->find( '#tinypng_api_key_name' )->getAttribute( 'value' )
		);

		$this->assertEquals(
			'developers@voormedia.com',
			$this->find( '#tinypng_api_key_email' )->getAttribute( 'value' )
		);
	}

	public function test_settings_should_not_send_registration_without_name() {
		$this->find( '#tinypng_api_key_name' )->clear();
		$this->find( '#tinypng_api_key_email' )->clear()->sendKeys( 'john@example.com' );
		$this->find( 'button[data-tiny-action=create-key]' )->click();

		$this->wait_for_text(
			'div.tiny-account-status div.create p.message',
			'Please enter your name'
		);

		$this->refresh();

		$this->assertEquals(
			'Register new account',
			$this->find( 'div.tiny-account-status h4' )->getText()
		);
	}

	public function test_settings_should_not_send_registration_without_email() {
		$this->find( '#tinypng_api_key_name' )->clear()->sendKeys( 'John' );
		$this->find( '#tinypng_api_key_email' )->clear();
		$this->find( 'button[data-tiny-action=create-key]' )->click();

		$this->wait_for_text(
			'div.tiny-account-status div.create p.message',
			'Please enter your email address'
		);

		$this->refresh();

		$this->assertEquals(
			'Register new account',
			$this->find( 'div.tiny-account-status h4' )->getText()
		);
	}

	public function test_settings_should_store_registration_key() {
		$this->find( '#tinypng_api_key_name' )->clear()->sendKeys( 'John' );
		$this->find( '#tinypng_api_key_email' )->clear()->sendKeys( 'john@example.com' );
		$this->find( 'button[data-tiny-action=create-key]' )->click();

		$this->wait_for_text(
			'div.tiny-account-status p.status',
			'An email has been sent with a link to activate your account'
		);

		$this->refresh();

		$this->assertEquals(
			'An email has been sent with a link to activate your account',
			$this->find( 'div.tiny-account-status p.status' )->getText()
		);
	}

	public function test_settings_should_enable_all_sizes_by_default() {
		$enabled_sizes = $this->get_enabled_sizes();

		$this->assertContains( 'tinypng_sizes[0]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[thumbnail]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[medium]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[large]', $enabled_sizes );
	}

	public function test_settings_should_store_enabled_sizes() {
		$this->find( '#tinypng_sizes_medium' )->click();
		$this->find( '#tinypng_sizes_0' )->click();
		$this->find( 'form' )->submit();

		$enabled_sizes = $this->get_enabled_sizes();

		$this->assertNotContains( 'tinypng_sizes[0]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[thumbnail]', $enabled_sizes );
		$this->assertNotContains( 'tinypng_sizes[medium]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[large]', $enabled_sizes );
	}

	public function test_settings_should_store_all_disabled_sizes() {
		$checkboxes = $this->find_all(
			'input[type=checkbox][checked][name^=tinypng_sizes]'
		);

		foreach ( $checkboxes as $checkbox ) {
			$checkbox->click();
		}

		$this->find( 'form' )->submit();

		$enabled_sizes = $this->get_enabled_sizes();
		$this->assertEquals( 0, count( $enabled_sizes ) );
	}

	public function test_settings_should_show_free_compressions() {
		$this->enable_compression_sizes(
			array( '0', 'thumbnail', 'medium', 'large' )
		);

		$this->refresh();

		$this->assertContains(
			'With these settings you can compress at least 125 images for free each month.',
			$this->find( '#tiny-image-sizes-notice' )->getText()
		);
	}

	public function test_settings_should_update_free_compressions() {
		$this->enable_compression_sizes(
			array( '0', 'thumbnail', 'medium', 'large' )
		);

		$this->refresh();
		$this->find( '#tinypng_sizes_medium' )->click();

		$this->assertContains(
			'With these settings you can compress at least 166 images for free each month.',
			$this->find( '#tiny-image-sizes-notice' )->getText()
		);
	}

	public function test_settings_should_show_no_compressions() {
		$checkboxes = $this->find_all(
			'input[type=checkbox][checked][name^=tinypng_sizes]'
		);

		foreach ( $checkboxes as $checkbox ) {
			$checkbox->click();
		}

		$this->assertContains(
			'With these settings no images will be compressed.',
			$this->find( '#tiny-image-sizes-notice' )->getText()
		);
	}

	public function test_settings_should_show_resizing_when_original_enabled() {
		$elements = $this->find_all( 'label[for=tinypng_resize_original_enabled]' );
		$this->assertEquals(
			'Resize and compress the original image',
			$elements[0]->getText()
		);

		$elements = $this->find_all( 'p.tiny-resize-unavailable' );
		$this->assertEquals(
			'',
			$elements[0]->getText()
		);
	}

	public function test_settings_should_not_show_resizing_when_original_disabled() {
		$this->find( '#tinypng_sizes_0' )->click(); /* Enabled by default */

		$elements = $this->find_all( 'label[for=tinypng_resize_original_enabled]' );
		$this->assertEquals(
			'',
			$elements[0]->getText()
		);

		$elements = $this->find_all( 'p.tiny-resize-unavailable' );
		$this->assertEquals(
			'Enable compression of the original image size for more options.',
			$elements[0]->getText()
		);
	}

	public function test_settings_should_store_resizing_settings() {
		$this->find( '#tinypng_resize_original_enabled' )->click();
		$this->find( '#tinypng_resize_original_width' )->clear()->sendKeys( '234' );
		$this->find( '#tinypng_resize_original_height' )->clear()->sendKeys( '345' );
		$this->find( 'form' )->submit();

		$this->assertEquals(
			'234',
			$this->find( '#tinypng_resize_original_width' )->getAttribute( 'value' )
		);

		$this->assertEquals(
			'345',
			$this->find( '#tinypng_resize_original_height' )->getAttribute( 'value' )
		);
	}
}
