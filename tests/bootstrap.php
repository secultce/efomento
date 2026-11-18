<?php

// Remove the config cache so that phpunit.xml env vars (e.g. AUDITING_CONSOLE=true)
// take effect. Without this, the cache built by the Docker entrypoint overrides
// the values set by phpunit, causing auditing to be disabled in console mode.
$cacheFile = __DIR__.'/../bootstrap/cache/config.php';
if (is_file($cacheFile) && ! unlink($cacheFile)) {
    throw new RuntimeException('Unable to remove cached config for PHPUnit.');
}

require __DIR__.'/../vendor/autoload.php';
