<?php

use Phalcon\Loader;

/**
 * Composer packages
 */
require BASE_PATH.'/vendor/autoload.php';

$loader = new Loader();

/**
 * Register Namespaces
 */
$loader->registerNamespaces([
    'Cms'        => APP_PATH . '/common/library/',
    'Cms\Models' => APP_PATH . '/common/models/',
    'Cms\Forms' => APP_PATH . '/common/forms/',
    'Cms\Validations' => APP_PATH . '/common/validations/',
    'Phalcon' => BASE_PATH . '/vendor/phalcon/incubator/Library/Phalcon/',
]);

/**
 * Register module classes
 */
$loader->registerClasses([
    'Cms\Modules\Frontend\Module' => APP_PATH . '/modules/frontend/Module.php',
    'Cms\Modules\Admin\Module' => APP_PATH . '/modules/admin/Module.php',
    'Cms\Modules\Api\Module' => APP_PATH . '/modules/api/Module.php',
    'Cms\Modules\Cli\Module'      => APP_PATH . '/modules/cli/Module.php'
]);

$loader->register();
