<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class BulkCompressIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        clear_settings();
        clear_uploads();
        reset_webservice();
    }

    public function testBulkCompressActionShouldBePresentInMedia() {
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertEquals('Compress All Images', self::$driver->findElement(
            WebDriverBy::cssSelector('select[name="action"] option[value="tiny_bulk_compress"]')
        )->getText());
    }

    private function prepare($normal=1, $large=0) {
        $this->set_api_key('PNG123');
        $this->enable_compression_sizes(array());

        for ($i = 0; $i < $normal; $i++) {
            $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.jpg');
        }
        for ($i = 0; $i < $large; $i++) {
            $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        }

        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.gif');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.pdf');

        $this->enable_compression_sizes(array('thumbnail', 'medium', 'large'));
    }

    public function testBulkCompressFromMediaShouldOnlyCompressSelected() {
        $this->prepare(1, 2);

        self::$driver->get(wordpress('/wp-admin/upload.php?orderby=title&order=asc'));

        $checkboxes = self::$driver->findElements(WebDriverBy::cssSelector('tbody input[type="checkbox"]'));
        $checkboxes[0]->click();

        self::$driver->findElement(WebDriverBy::cssSelector('select[name="action"] option[value="tiny_bulk_compress"]'))->click();
        self::$driver->findElement(WebDriverBy::cssSelector('div.actions input[value="Apply"]'))->click();

        self::$driver->wait(3)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('.updated'), 'All images are processed'));

        $this->assertEquals('1', self::$driver->findElement(WebDriverBy::cssSelector('#tiny-progress span'))->getText());
        $this->assertEquals('input-example', self::$driver->findElement(WebDriverBy::cssSelector('.media-item .filename'))->getText());
    }

    public function testBulkCompressShouldCompressAll() {
        $this->prepare(1, 1);

        self::$driver->get(wordpress('/wp-admin/upload.php?page=tiny-bulk-compress.php'));
        $elements = self::$driver->findElements(WebDriverBy::cssSelector('#tiny-bulk-compress p'));
        $this->assertContains('2 images', $elements[1]->getText());

        self::$driver->findElement(WebDriverBy::cssSelector('#tiny-bulk-compress button'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('.updated'), 'All images are processed'));

        $elements = self::$driver->findElements(WebDriverBy::cssSelector('.media-item .filename'));
        $filenames = array_map('innerText', $elements);

        $this->assertEquals(2, count($filenames));
        $this->assertContains('input-example', $filenames);

        $this->assertEquals('2', self::$driver->findElement(WebDriverBy::cssSelector('#tiny-progress span'))->getText());
        $this->assertEquals('4', self::$driver->findElement(WebDriverBy::cssSelector('#tiny-status span'))->getText());
    }
}
