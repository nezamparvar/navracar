<?php

use IlluminateHttpRequest;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../navracar-staging-app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../navracar-staging-app/vendor/autoload.php';

(require_once __DIR__.'/../navracar-staging-app/bootstrap/app.php')->handleRequest(Request::capture());
