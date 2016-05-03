<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class CompressIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        clear_settings();
        clear_uploads();
    }

    public function testInvalidCredentialsShouldStillUploadImage()
    {
        $this->set_api_key('1234');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('input-example',
            self::$driver->findElement(WebDriverBy::xpath('//img[contains(@src, "input-example")]'))->getAttribute('src'));
    }

    public function testInvalidCredentialsShouldShowError()
    {
        $this->set_api_key('1234');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('Latest error: Credentials are invalid',
            self::$driver->findElement(WebDriverBy::id('tinify-compress-details'))->getText());
    }

    public function testShrink() {
        $this->set_api_key('PNG123');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('sizes compressed',
            self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
    }

    public function testCompressButton() {
        $this->enable_compression_sizes(array('medium'));
        $this->set_api_key('PNG123');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->enable_compression_sizes(array('medium', 'large'));

        self::$driver->get(wordpress('/wp-admin/upload.php'));
        $this->assertContains('1 size compressed',
            self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
        $this->assertContains('1 size not compressed',
            self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images button'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('td.tiny-compress-images'), '2 sizes compressed'));
    }

    public function testLimitReached() {
        $this->set_api_key('LIMIT123');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('Latest error: Your monthly limit has been exceeded',
            self::$driver->findElement(WebDriverBy::cssSelector('span.error_message'))->getText());
    }

    public function testLimitReachedDismisses() {
        $this->set_api_key('LIMIT123');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        self::$driver->findElement(WebDriverBy::cssSelector('.tiny-notice button, .tiny-notice a.tiny-dismiss'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::invisibilityOfElementWithText(
             WebDriverBy::cssSelector('.tiny-dismiss'), 'Dismiss'));

        self::$driver->get(wordpress('/wp-admin/options-media.php'));
        $this->assertEquals(0, count(self::$driver->findElements(WebDriverBy::cssSelector('div.error p'))));
    }

    public function testIncorrectJsonButton() {
        $this->enable_compression_sizes(array());
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->enable_compression_sizes(array('medium', 'large'));

        $this->set_api_key('JSON1234');
        self::$driver->get(wordpress('/wp-admin/upload.php'));

        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images button'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('td.tiny-compress-images'), 'JSON: Syntax error [4]'));
    }

    public function testResizeFitShouldDisplayResizedTextInMediaLibrary() {
        $this->set_api_key('PNG123');
        $this->enable_resize(300, 200);
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images a.thickbox'))->click();
        $this->assertContains('resized to 300x200',
            self::$driver->findElement(WebDriverBy::cssSelector('div.tiny-compression-details'))->getText());
    }

    public function testResizeFitShouldDisplayResizedTextInEditScreen() {
        if (!$this->has_postbox_container()) return;
        $this->set_api_key('PNG123');
        $this->enable_resize(300, 200);
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->view_edit_image();
        $this->assertContains('Dimensions: 300 × 200',
            self::$driver->findElement(WebDriverBy::cssSelector($this->postbox_dimension_selector()))->getText());
    }

    public function testResizeScaleShouldDisplayResizedTextInMediaLibrary() {
        $this->set_api_key('PNG123');
        $this->enable_resize(0, 200);
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images a.thickbox'))->click();
        $this->assertContains('resized to 300x200', self::$driver->findElement(
            WebDriverBy::cssSelector('div.tiny-compression-details'))->getText());
    }

    public function testResizeScaleShouldDisplayResizedTextInEditScreen() {
        if (!$this->has_postbox_container()) return;
        $this->set_api_key('PNG123');
        $this->enable_resize(0, 200);
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->view_edit_image();
        $this->assertContains('Dimensions: 300 × 200',
            self::$driver->findElement(WebDriverBy::cssSelector($this->postbox_dimension_selector()))->getText());
    }

    public function testResizeNotNeededShouldNotDisplayResizedTextInMediaLibrary() {
        $this->set_api_key('PNG123');
        $this->enable_resize(30000, 20000);
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images a.thickbox'))->click();
        $this->assertNotContains('resized',
            self::$driver->findElement(WebDriverBy::cssSelector('div.tiny-compression-details'))->getText());
    }

    public function testResizeNotNeededShouldDisplayOriginalDimensionsInEditScreen() {
        if (!$this->has_postbox_container()) return;
        $this->set_api_key('PNG123');
        $this->enable_resize(30000, 20000);
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->view_edit_image();
        $this->assertContains('Dimensions: 1080 × 720',
            self::$driver->findElement(WebDriverBy::cssSelector($this->postbox_dimension_selector()))->getText());
    }

    public function testResizeDisabledShouldNotDisplayResizedTextInMediaLibrary() {
        $this->set_api_key('PNG123');
        $this->enable_resize(300, 200);
        $this->disable_resize();
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images a.thickbox'))->click();
        $this->assertNotContains('resized',
            self::$driver->findElement(WebDriverBy::cssSelector('div.tiny-compression-details'))->getText());
    }

    public function testResizeDisabledShouldDisplayOriginalDimensionsInEditScreen() {
        if (!$this->has_postbox_container()) return;
        $this->set_api_key('PNG123');
        $this->enable_resize(300, 200);
        $this->disable_resize();
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->view_edit_image();
        $this->assertContains('Dimensions: 1080 × 720',
            self::$driver->findElement(WebDriverBy::cssSelector($this->postbox_dimension_selector()))->getText());
    }

    public function testPreserveCopyrightShouldDisplayCorrectImageSizeInMediaLibrary() {
        $this->set_api_key('PRESERVEJPG123');
        $this->enable_preserve(array('copyright'));
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-copyright.jpg');
        $this->assertNotContains('files modified after compression',
            self::$driver->findElement(WebDriverBy::cssSelector('div#tinify-compress-details'))->getText());
    }

    public function testShouldShowDetailsInEditScreen() {
        if (!$this->has_postbox_container()) return;
        $this->set_api_key('PNG123');
        $this->enable_compression_sizes(array());
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->enable_compression_sizes(array('medium', 'large'));
        $this->view_edit_image();
        $this->assertContains("Compress JPEG & PNG Images\n2 sizes not compressed\nDetails\nCompress",
            self::$driver->findElement(WebDriverBy::cssSelector('div.postbox-container div.tiny-compress-images'))->getText());
    }

    public function testButtonInEditScreenShouldCompressImages() {
        if (!$this->has_postbox_container()) return;
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->set_api_key('PNG123');
        $this->enable_compression_sizes(array('medium', 'large'));
        $this->view_edit_image();
        self::$driver->findElement(WebDriverBy::cssSelector('div.tiny-compress-images button.tiny-compress'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('div.tiny-compress-images'), '2 sizes compressed'));
    }

    public function testEditScreenShouldShowDetailsPopup() {
        if (!$this->has_postbox_container()) return;
        $this->set_api_key('PNG123');
        $this->enable_compression_sizes(array('medium', 'large'));
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->view_edit_image();
        self::$driver->findElement(WebDriverBy::cssSelector('div.tiny-compress-images a.thickbox'))->click();
        $this->assertContains('Compression details for input-example.jpg',
            self::$driver->findElement(WebDriverBy::cssSelector('div.tiny-compression-details'))->getText());
    }

    public function testEditScreenShouldShowCorrectDetailsInPopup() {
        if (!$this->has_postbox_container()) return;
        $this->set_api_key('PNG123');
        $this->enable_compression_sizes(array('medium', 'large'));
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->view_edit_image();

        self::$driver->findElement(WebDriverBy::cssSelector('div.tiny-compress-images a.thickbox'))->click();
        $cells = self::$driver->findElements(WebDriverBy::cssSelector('div.tiny-compression-details td'));
        $texts = array_map('innerText', $cells);
        // Remove sizes that are version specific
        for ($i = 0; $i < count($texts); ) {
            $row_size = (substr($texts[$i+2], 0, 3) == 'Not') ? 3 : 4;
            if (in_array($texts[$i], array('original', 'thumbnail', 'medium', 'large', 'Combined'))) {
                $i += $row_size;
            } else {
                $texts = array_merge(array_slice($texts, 0, $i), array_slice($texts, $i + $row_size));
            }
        }

        $this->assertEquals(array(
            'original',  '158.1 kB', 'Not configured to be compressed',
            'large',     '158.1 kB', '147.5 kB', '1 min ago',
            'medium',    '158.1 kB', '147.5 kB', '1 min ago',
            'thumbnail', '11.8 kB',  'Not configured to be compressed',
            'Combined',  '316.2 kB', '295.0 kB', ''), $texts);
    }

    public function testDifferentImageFormatFileShouldNotShowCompressInfoInMediaLibrary()
    {
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.gif');
        $this->assertEquals('',
            self::$driver->findElement(WebDriverBy::cssSelector('div#tinify-compress-details'))->getText());
    }

    public function testNonImageFileShouldNotShowCompressInfoInMediaLibrary()
    {
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.pdf');
        $this->assertEquals('',
            self::$driver->findElement(WebDriverBy::cssSelector('div#tinify-compress-details'))->getText());
    }

    public function testGatewayTimeoutShouldBeDetectedInShrink()
    {
        $this->enable_compression_sizes(array('medium'));
        $this->set_api_key('GATEWAYTIMEOUT');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('JSON: Syntax error [4]',
            self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
    }

    public function testGatewayTimeoutShouldBeDetectedInOutput()
    {
        $this->enable_compression_sizes(array('0', 'medium'));
        $this->enable_preserve(array('copyright'));
        $this->set_api_key('PNG123_GATEWAYTIMEOUT');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('Unexepected error in output',
            self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
    }

    public function testErrorShouldBeDetectedInOutput()
    {
        $this->enable_compression_sizes(array('0', 'medium'));
        $this->enable_preserve(array('copyright'));
        $this->set_api_key('PNG123_INVALID');
        $this->upload_media(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains("Metadata key 'author' not supported",
            self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
    }
}
