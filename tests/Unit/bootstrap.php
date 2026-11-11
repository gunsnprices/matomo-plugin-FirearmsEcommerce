<?php

/**
 * Bootstrap for running the unit tests without Matomo:
 *   php path/to/phpunit -c plugins/FirearmsEcommerce/phpunit.xml.dist
 *
 * Inside Matomo (./console tests:run FirearmsEcommerce) Matomo's own bootstrap is used instead.
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'Piwik\\Plugins\\FirearmsEcommerce\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = dirname(__DIR__, 2) . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
