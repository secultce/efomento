<?php

// Remove the config cache so that phpunit.xml env vars (e.g. AUDITING_CONSOLE=true)
// take effect. Without this, the cache built by the Docker entrypoint overrides
// the values set by phpunit, causing auditing to be disabled in console mode.
@unlink(__DIR__.'/../bootstrap/cache/config.php');

require __DIR__.'/../vendor/autoload.php';
