<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class SettingsIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
        self::$driver->get(wordpress('/wp-admin/options-media.php'));
    }

    public function tearDown() {
        clear_settings();
    }

    public function testTitlePresence()
    {
        $headings = self::$driver->findElements(WebDriverBy::cssSelector('h1, h2, h3, h4'));
        $texts = array_map('innerText', $headings);
        $this->assertContains('PNG and JPEG optimization', $texts);
    }

    public function testApiKeyInputPresence() {
        $elements = self::$driver->findElements(WebDriverBy::name('tinypng_api_key'));
        $this->assertEquals(1, count($elements));
    }

    public function testShouldPersistApiKey() {
        $element = $this->set_api_key('1234');
        $this->assertEquals('1234', $element->getAttribute('value'));
    }

    public function testShouldShowNoticeIfNoApiKeyIsSet() {
        $element = self::$driver->findElement(WebDriverBy::cssSelector('.error a'));
        $this->assertStringEndsWith('options-media.php#tiny-compress-images', $element->getAttribute('href'));
    }

    public function testShouldShowNoNoticeIfApiKeyIsSet() {
        $this->set_api_key('1234');
        $elements = self::$driver->findElements(WebDriverBy::cssSelector('.error a'));
        $this->assertEquals(0, count($elements));
    }

    public function testNoApiKeyNoticeShouldLinkToSettings() {
        self::$driver->findElement(WebDriverBy::cssSelector('.error a'))->click();
        $this->assertStringEndsWith('options-media.php#tiny-compress-images', self::$driver->getCurrentURL());
    }

    public function testDefaultSizesBeingCompressed() {
        $elements = self::$driver->findElements(
            WebDriverBy::xpath('//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]'));
        $size_ids = array_map('elementName', $elements);
        $this->assertContains('tinypng_sizes[0]', $size_ids);
        $this->assertContains('tinypng_sizes[thumbnail]', $size_ids);
        $this->assertContains('tinypng_sizes[medium]', $size_ids);
        $this->assertContains('tinypng_sizes[large]', $size_ids);
    }

    public function testShouldPersistSizes() {
        $element = self::$driver->findElement(WebDriverBy::id('tinypng_sizes_medium'));
        $element->click();
        $element = self::$driver->findElement(WebDriverBy::id('tinypng_sizes_0'));
        $element->click();
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();

        $elements = self::$driver->findElements(
            WebDriverBy::xpath('//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]'));
        $size_ids = array_map('elementName', $elements);
        $this->assertNotContains('tinypng_sizes[0]', $size_ids);
        $this->assertContains('tinypng_sizes[thumbnail]', $size_ids);
        $this->assertNotContains('tinypng_sizes[medium]', $size_ids);
        $this->assertContains('tinypng_sizes[large]', $size_ids);
    }

    public function testShouldPersistNoSizes() {
        $elements = self::$driver->findElements(
            WebDriverBy::xpath('//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]'));
        foreach ($elements as $element) {
            $element->click();
        }
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();

        $elements = self::$driver->findElements(
            WebDriverBy::xpath('//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]'));
        $this->assertEquals(0, count(array_map('elementName', $elements)));
    }

    public function testShouldShowTotalImagesInfo() {
        $this->enable_compression_sizes(array('0', 'thumbnail', 'medium', 'large'));
        $element = self::$driver->findElement(WebDriverBy::id('tiny-image-sizes-notice'));
        $this->assertContains('With these settings you can compress at least 125 images for free each month.', $element->getText());
    }

    public function testShouldUpdateTotalImagesInfo() {
        $this->enable_compression_sizes(array('0', 'thumbnail', 'medium', 'large'));
        $element = self::$driver->findElement(
            WebDriverBy::xpath('//input[@type="checkbox" and @name="tinypng_sizes[0]" and @checked="checked"]'));
        $element->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#tiny-image-sizes-notice'),
            'With these settings you can compress at least 166 images for free each month.'));
    }

    public function testShouldShowCorrectNoImageSizesInfo() {
        $elements = self::$driver->findElements(
            WebDriverBy::xpath('//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]'));
        foreach ($elements as $element) {
            $element->click();
        }
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#tiny-image-sizes-notice'), 'With these settings no images will be compressed.'));
        // Not really necessary anymore to assert this.
        $elements = self::$driver->findElement(WebDriverBy::id('tiny-image-sizes-notice'))->findElements(WebDriverBy::tagName('p'));
        $statuses = array_map('innerText', $elements);
        $this->assertContains('With these settings no images will be compressed.', $statuses);
    }

    public function testShouldShowResizingWhenOriginalEnabled() {
        $element = self::$driver->findElement(WebDriverBy::id('tinypng_sizes_0'));
        if (!$element->getAttribute('checked')) {
            $element->click();
        }
        $labels = self::$driver->findElements(WebDriverBy::tagName('label'));
        $texts = array_map('innerText', $labels);
        $this->assertContains('Resize and compress the orginal image', $texts);
        $paragraphs = self::$driver->findElements(WebDriverBy::tagName('p'));
        $texts = array_map('innerText', $paragraphs);
        $this->assertNotContains('Enable compression of the original image size for more options.', $texts);
    }

    public function testShouldNotShowResizingWhenOriginalDisabled() {
        $element = self::$driver->findElement(WebDriverBy::id('tinypng_sizes_0'));
        if ($element->getAttribute('checked')) {
            $element->click();
        }
        self::$driver->wait(1)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('p.tiny-resize-unavailable'), 'Enable compression of the original image size for more options.'));
        $labels = self::$driver->findElements(WebDriverBy::tagName('label'));
        $texts = array_map('innerText', $labels);
        $this->assertNotContains('Resize and compress orginal images to fit within:', $texts);
    }

    public function testShouldNotShowResizingWhenOriginalDisabledWhenShownFirst() {
        $this->enable_compression_sizes(array('original'));
        self::$driver->navigate()->refresh();
        $this->assertEquals('Enable compression of the original image size for more options.',
            self::$driver->findElement(WebDriverBy::cssSelector('.tiny-resize-unavailable'))->getText());
    }

    public function testShouldPersistResizingSettings() {
        $this->enable_resize(123, 456);
        $this->assertEquals('123', self::$driver->findElement(WebDriverBy::id('tinypng_resize_original_width'))->getAttribute('value'));
        $this->assertEquals('456', self::$driver->findElement(WebDriverBy::id('tinypng_resize_original_height'))->getAttribute('value'));
    }

    public function testStatusPresenceOK() {
        reset_webservice();
        $this->set_api_key('PNG123');
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#tiny-compress-status'),
           "API connection successful\nYou have made"));
    }

    public function testStatusPresenseFail() {
        $this->set_api_key('INVALID123');
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#tiny-compress-status'),
           "API connection unsuccessful\nError: Credentials are invalid"));
    }

    public function testShouldShowBulkCompressionLink() {
        reset_webservice();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#tiny-compress-savings p'),
            'No images compressed yet. Use Compress All Images to compress existing images.'));
    }

    public function testShouldShowSavings() {
        reset_webservice();
        $this->set_api_key('PNG123');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        self::$driver->get(wordpress('/wp-admin/options-media.php'));
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#tiny-compress-savings p'),
            'You have saved a total of'));
    }
}
