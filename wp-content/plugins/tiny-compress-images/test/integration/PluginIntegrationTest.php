<?php

require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';

use Facebook\WebDriver\WebDriverBy;

class PluginIntegrationTest extends IntegrationTestCase {
	public function set_up() {
		parent::set_up();
		$this->visit( '/wp-admin/plugins.php' );
	}

	public function tear_down() {
		parent::tear_down();
		clear_settings();
	}

	public function test_plugin_list_should_include_title() {
		$element = $this->find(
			'tr[data-slug=tiny-compress-images] td.plugin-title strong, ' . /* WP4.5+ */
			'tr#compress-jpeg-png-images td.plugin-title strong'
		);

		$this->assertEquals(
			'Compress JPEG & PNG images',
			$element->getText()
		);
	}

	public function test_plugin_list_should_include_settings_link() {
		$element = $this->find(
			'tr[data-slug=tiny-compress-images] span.settings a, ' . /* WP4.5+ */
			'tr#compress-jpeg-png-images span.settings a'
		);

		$this->assertStringEndsWith(
			'options-media.php#tiny-compress-images',
			$element->getAttribute( 'href' )
		);
	}
}
