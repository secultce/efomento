<?php

namespace Tests\Unit;

use App\Models\ProfileSnapshot;
use Tests\TestCase;

class ProfileSnapshotHiddenAttributesTest extends TestCase
{
    public function test_profile_snapshot_nao_declara_atributos_hidden(): void
    {
        $snapshot = new ProfileSnapshot;

        $this->assertSame([], $snapshot->getHidden());
    }
}
