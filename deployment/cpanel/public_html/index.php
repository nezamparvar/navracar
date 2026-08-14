<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// The Laravel application is stored outside the public web root on cPanel.
if (file_exists($maintenance = __DIR__.'/../navracar-app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../navracar-app/vendor/autoload.php';

(require_once __DIR__.'/../navracar-app/bootstrap/app.php')
    ->handleRequest(Request::capture());
