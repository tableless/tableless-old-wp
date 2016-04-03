<?php

require 'vendor/autoload.php';

use Facebook\WebDriver;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

function wordpress($url = null) {
    return getenv('WORDPRESS_URL') . $url;
}

function wordpress_version() {
    return intval(getenv('WORDPRESS_VERSION'));
}

function configure_wordpress_for_testing($driver) {
    if (is_wordpress_setup()) {
        restore_wordpress_site();
        set_siteurl(wordpress());
        login($driver);
        clear_uploads($driver);
    } else {
        if (wordpress_version() > 30) {
            setup_wordpress_language($driver);
        }
        setup_wordpress_site($driver);
        set_siteurl(wordpress());
        login($driver);
        activate_plugin($driver);
        backup_wordpress_site();
    }
}

function restore_wordpress() {
    if (is_wordpress_setup()) {
        set_siteurl('http://' . getenv('HOST_IP') . ':' . getenv('HOST_PORT'));
    }
}

function mysql_dump_file() {
    return dirname(__FILE__) . '/../../tmp/mysqldump_' . getenv('WORDPRESS_DATABASE') . '.sql.gz';
}

function restore_wordpress_site() {
    shell_exec('gunzip -c < ' . mysql_dump_file() . ' | mysql -h ' . getenv('HOST_IP') . ' -u root -p' . getenv('MYSQL_ROOT_PASSWORD') . ' ' . getenv('WORDPRESS_DATABASE'));
}

function backup_wordpress_site() {
    shell_exec('mysqldump -h ' . getenv('HOST_IP') . ' -u root -p' . getenv('MYSQL_ROOT_PASSWORD') . ' ' . getenv('WORDPRESS_DATABASE') . ' | gzip -c > ' . mysql_dump_file());
}

function set_siteurl($site_url) {
    $db = new mysqli(getenv('HOST_IP'), 'root', getenv('MYSQL_ROOT_PASSWORD'), getenv('WORDPRESS_DATABASE'));
    $statement = $db->prepare("UPDATE wp_options SET option_value = ? WHERE option_name = 'home' OR option_name = 'siteurl'");
    $statement->bind_param('s', $site_url);
    $statement->execute();
}

function clear_settings() {
    $db = new mysqli(getenv('HOST_IP'), 'root', getenv('MYSQL_ROOT_PASSWORD'), getenv('WORDPRESS_DATABASE'));
    $statement = $db->prepare("DELETE FROM wp_options WHERE option_name LIKE 'tinypng_%'");
    $statement->execute();
    $statement = $db->prepare("DELETE FROM wp_usermeta WHERE meta_key LIKE 'tinypng_%'");
    $statement->execute();
}

function clear_uploads() {
    $db = new mysqli(getenv('HOST_IP'), 'root', getenv('MYSQL_ROOT_PASSWORD'), getenv('WORDPRESS_DATABASE'));
    $statement = $db->prepare("DELETE wp_postmeta FROM wp_postmeta JOIN wp_posts ON wp_posts.ID = wp_postmeta.post_id WHERE wp_posts.post_type = 'attachment'");
    $statement->execute();
    $statement = $db->prepare("DELETE FROM wp_posts WHERE wp_posts.post_type = 'attachment'");
    $statement->execute();
    shell_exec('docker exec -it wordpress' . getenv('WORDPRESS_VERSION') . ' rm -rf wp-content/uploads');
}

function is_wordpress_setup() {
    $db = new mysqli(getenv('HOST_IP'), 'root', getenv('MYSQL_ROOT_PASSWORD'));
    if ($result = $db->query("SELECT * FROM information_schema.tables WHERE table_schema = '" . getenv('WORDPRESS_DATABASE') . "'")) {
        return $result->num_rows > 0;
    } else {
        return false;
    }
}

function setup_wordpress_language($driver) {
    $driver->get(wordpress('/wp-admin/install.php'));
    $driver->findElement(WebDriverBy::tagName('form'))->submit();
}

function setup_wordpress_site($driver) {
    if ($driver->getCurrentURL() != wordpress('/wp-admin/install.php?step=1')) {
        $driver->get(wordpress('/wp-admin/install.php'));
    }
    $driver->findElement(WebDriverBy::name('weblog_title'))->sendKeys('Wordpress test');
    $driver->findElement(WebDriverBy::name('user_name'))->clear()->sendKeys('admin');
    if (wordpress_version() > 42) {
        $driver->findElement(WebDriverBy::id('pass1-text'))->clear()->sendKeys('a')->sendKeys('dmin');
    } else {
        $driver->findElement(WebDriverBy::name('admin_password'))->sendKeys('admin');
        $driver->findElement(WebDriverBy::name('admin_password2'))->sendKeys('admin');
    }
    $driver->findElement(WebDriverBy::name('admin_email'))->sendKeys('developers@voormedia.com');
    $driver->findElement(WebDriverBy::tagName('form'))->submit();
    $h1s = $driver->findElements(WebDriverBy::tagName('h1'));
    $texts = array_map("innerText", $h1s);
    if (array_search('Success', $texts) >= 0) {
        print "Setting up WordPress is successful.\n";
    } else {
        var_dump($driver->getPageSource());
        throw new UnexpectedValueException('Setting up WordPress failed.');
    }
}

function login($driver) {
    print "Logging in to WordPress... ";
    $driver->get(wordpress('/wp-login.php'));
    $driver->findElement(WebDriverBy::tagName('body'))->click();
    $driver->findElement(WebDriverBy::name('log'))->clear()->click()->sendKeys('admin');
    $driver->findElement(WebDriverBy::name('pwd'))->clear()->click()->sendKeys('admin');
    $driver->findElement(WebDriverBy::tagName('form'))->submit();

    try {
        $dashboardHeading = $driver->findElement(WebDriverBy::xpath("//html/body//div[@class='wrap']/*[self::h1 or self::h2]"));
        if ($dashboardHeading->getText() == 'Dashboard') {
            print "success!\n";
        } else {
            var_dump($driver->getPageSource());
            throw new UnexpectedValueException('Login failed.');
        }
    } catch (Exception $e) {
        // Fixme: sometimes we get a login error randomly. Perhaps it's caused by one of the tests.
        var_dump($driver->getPageSource());
        throw new UnexpectedValueException('Login failed.');
    }
}

function activate_plugin($driver) {
    $driver->get(wordpress('/wp-admin/plugins.php'));
    $activate_links = $driver->findElements(WebDriverBy::xpath('//a[starts-with(@href, "plugins.php?action=activate&plugin=tinypng-image-compression")]'));
    $deactivate_links = $driver->findElements(WebDriverBy::xpath('//a[starts-with(@href, "plugins.php?action=deactivate&plugin=tinypng-image-compression")]'));
    if (count($activate_links) > 0) {
        $activate_links[0]->click();
    } elseif (count($deactivate_links) > 0) {
        print "Plugin already activated.\n";
    } else {
        var_dump($driver->getPageSource());
        throw new UnexpectedValueException('Activating plugin failed.');
    }
    $driver->get(wordpress('/wp-admin/upload.php?mode=list'));
}

function close_webdriver() {
    if (isset($GLOBALS['global_session_id']) && isset($GLOBALS['global_webdriver_host'])) {
        RemoteWebDriver::createBySessionId($GLOBALS['global_session_id'], $GLOBALS['global_webdriver_host'])->close();
    }
}

function reset_webservice() {
    $request = curl_init();
    curl_setopt_array($request, array(
        CURLOPT_URL => 'http://' . getenv('HOST_IP') .':8080/reset',
    ));
    $response = curl_exec($request);
    curl_close($request);
}

// $global_webdriver_host = 'http://127.0.0.1:4444/wd/hub';
// $global_driver = RemoteWebDriver::create($global_webdriver_host, DesiredCapabilities::firefox());
$global_webdriver_host = 'http://127.0.0.1:8910';
$global_driver = RemoteWebDriver::create($global_webdriver_host, DesiredCapabilities::phantomjs());
$global_session_id = $global_driver->getSessionID();
$window = new WebDriverDimension(1280, 1024);
$global_driver->manage()->window()->setSize($window);

register_shutdown_function('close_webdriver');
register_shutdown_function('restore_wordpress');

configure_wordpress_for_testing($global_driver);
