<?php

namespace Wpae\App\Activation;


class Installer
{
    const MIN_PHP_VERSION = "5.3.0";

    const WRONG_PHP_VERSION_MESSAGE = "WP All Export requires PHP %1s or greater, you are using PHP %2s. Please contact your host and tell them to update your server to at least PHP %1s.";

    public function checkActivationConditions()
    {
        if (version_compare(phpversion(), self::MIN_PHP_VERSION  , "<")) {
            $this->error(sprintf(
                self::WRONG_PHP_VERSION_MESSAGE,
                self::MIN_PHP_VERSION,
                phpversion(),
                self::MIN_PHP_VERSION
            ));
        }
    }

    private function error($message){

        echo '<div class="error"><p style="font-size:14px;">' . sprintf(__($message)) . '</p></div>'; die;
    }
}