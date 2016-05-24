<?php

require(dirname(__FILE__) . '/../helpers/integration_helper.php');
require(dirname(__FILE__) . '/../helpers/setup.php');

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\UselessFileDetector;

abstract class IntegrationTestCase extends PHPUnit_Framework_TestCase {

    protected static $driver;

    public static function setUpBeforeClass() {
        self::$driver = RemoteWebDriver::createBySessionId($GLOBALS['global_session_id'], $GLOBALS['global_webdriver_host']);
    }

    protected function has_postbox_container() {
        return wordpress_version() >= 35;
    }

    protected function postbox_dimension_selector() {
        $version = wordpress_version();
        if ($version < 37)
            return 'div.misc-pub-section:nth-child(5)';
        elseif ($version == 37)
            return 'div.misc-pub-section:nth-child(6)';
        else
            return 'div.misc-pub-dimensions';
    }

    protected function upload_media($path) {
        self::$driver->get(wordpress('/wp-admin/media-new.php?browser-uploader&flash=0'));
        $links = self::$driver->findElements(WebDriverBy::xpath('//a[text()="browser uploader"]'));
        if (count($links) > 0) {
            $link = $links[0];
            if ($link->isDisplayed()) {
                $link->click();
            }
        }
        self::$driver->wait(2)->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::name('async-upload')));
        $file_input = self::$driver->findElement(WebDriverBy::name('async-upload'));
        $file_input->setFileDetector(new UselessFileDetector());
        $file_input->sendKeys($path);
        self::$driver->findElement(WebDriverBy::xpath('//input[@value="Upload"]'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath('//h1[contains(text(),"Media Library")]|//h2[contains(text(),"Media Library")]')));
    }

    protected function set_api_key($api_key) {
        $url = wordpress('/wp-admin/options-media.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        self::$driver->findElement(WebDriverBy::name('tinypng_api_key'))->clear()->sendKeys($api_key);
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();
        return self::$driver->findElement(WebDriverBy::name('tinypng_api_key'));
    }

    protected function enable_compression_sizes($sizes) {
        $url = wordpress('/wp-admin/options-media.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        $elements = self::$driver->findElements(WebDriverBy::xpath('//input[starts-with(@id, "tinypng_sizes_")]'));
        foreach($elements as $element) {
            $size = str_replace('tinypng_sizes_', '', $element->getAttribute('id'));
            if (in_array($size, $sizes)) {
                if (!$element->getAttribute('checked')) {
                    $element->click();
                }
            } else {
                if ($element->getAttribute('checked')) {
                    $element->click();
                }
            }
        }
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();
    }

    protected function enable_resize($width, $height) {
        $url = wordpress('/wp-admin/options-media.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        $element = self::$driver->findElement(WebDriverBy::id('tinypng_resize_original_enabled'));
        if (!$element->getAttribute('checked')) {
            $element->click();
        }
        self::$driver->findElement(WebDriverBy::id('tinypng_resize_original_width'))->clear()->sendKeys($width);
        self::$driver->findElement(WebDriverBy::id('tinypng_resize_original_height'))->clear()->sendKeys($height);
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();
    }

    protected function disable_resize() {
        $url = wordpress('/wp-admin/options-media.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        $element = self::$driver->findElement(WebDriverBy::id('tinypng_resize_original_enabled'));
        if ($element->getAttribute('checked')) {
            $element->click();
        }
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();
    }

    protected function enable_preserve($keys) {
        $url = wordpress('/wp-admin/options-media.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        $elements = self::$driver->findElements(WebDriverBy::xpath('//input[starts-with(@id, "tinypng_preserve_data")]'));
        foreach($elements as $element) {
            $key = str_replace('tinypng_preserve_data_', '', $element->getAttribute('id'));
            if (in_array($key, $keys)) {
                if (!$element->getAttribute('checked')) {
                    $element->click();
                }
            } else {
                if ($element->getAttribute('checked')) {
                    $element->click();
                }
            }
        }
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();
    }

    protected function view_edit_image($image_title = 'input-example') {
        $url = wordpress('/wp-admin/upload.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        if (wordpress_version() >= 43) {
            $selector = "//span[text()='" . $image_title . "']";
        } else {
            $selector = "//a[contains(text(),'" . $image_title . "')]";
        }
        self::$driver->findElement(WebDriverBy::xpath($selector))->click();
    }
}
