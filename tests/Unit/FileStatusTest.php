<?php

namespace Tests\Unit;

use App\Enums\FileStatus;
use PHPUnit\Framework\TestCase;

class FileStatusTest extends TestCase
{
    public function test_file_status_labels()
    {
        $this->assertEquals('De acordo', FileStatus::VALID->label());
        $this->assertEquals('Necessita de ajuste', FileStatus::INVALID->label());
    }
}
