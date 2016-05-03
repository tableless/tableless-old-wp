<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

use Facebook\WebDriver\WebDriverBy;

class PluginIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
        self::$driver->get(wordpress('/wp-admin/plugins.php'));
    }

    public function tearDown() {
        clear_settings();
    }

    public function testTitlePresence()
    {
        $element = self::$driver->findElements(WebDriverBy::xpath('//*[@id="compress-jpeg-png-images"]//a[text()="Settings"]'));
        $this->assertStringEndsWith('options-media.php#tiny-compress-images', $element[0]->getAttribute('href'));
    }
}
