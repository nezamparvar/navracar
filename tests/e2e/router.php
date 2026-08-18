<?php

/**
 * E2E-only router for PHP's built-in server (php -S), used by serve.mjs. A copy of Laravel's
 * own vendor/laravel/framework/.../server.php with one addition: forces "Connection: close".
 *
 * PHP's built-in dev server is single-threaded and has a long-standing bug handling HTTP
 * keep-alive: Chromium reuses a persistent connection for a second request to the same
 * origin, and php -S can take ~30s to respond on that reused connection instead of responding
 * immediately (reproduced directly: a bare second page.goto() to an already-visited route
 * took 30.4s under Playwright, vs ~30ms for the same request via curl on a fresh connection).
 * Forcing "Connection: close" makes every request open a fresh TCP connection instead of
 * reusing a stale one, which avoids the hang entirely.
 *
 * This file is E2E-test-only infrastructure — never used by staging/production, which run
 * php-fpm behind nginx (see DEPLOY-*.md), not php -S. It does not touch any application or
 * framework code path.
 */

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Static files (compiled CSS/JS, images) — let php -S serve them directly, same as Laravel's
// own server.php. The keep-alive hang is specific to dynamic responses that vary in timing,
// so this keeps static-asset serving on its normal fast path.
if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

header('Connection: close');

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
