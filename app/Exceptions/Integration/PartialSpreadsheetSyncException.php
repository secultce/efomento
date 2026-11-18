<?php

namespace App\Exceptions\Integration;

use RuntimeException;

/** Carries partial tab counts after all rows have been attempted; individual errors remain in the logs. */
class PartialSpreadsheetSyncException extends RuntimeException
{
    public function __construct(
        public readonly int $processedCount,
        public readonly int $failedCount,
    ) {
        parent::__construct("{$failedCount} registro(s) não foram gravados; {$processedCount} registro(s) processados.");
    }
}
