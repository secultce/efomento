<?php

namespace Tests\Feature;

use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportGoogleSheetsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_with_registration_data_flag_is_passed_to_import_sheet(): void
    {
        $this->mock(GoogleSheetsService::class)
            ->shouldReceive('importSheet')
            ->once()
            ->with('sheet-id', 'Abertura', false, 1, null, true)
            ->andReturn(2);

        $this->artisan('app:import-google-sheets sheet-id --aba=Abertura --user-id=1 --with-registration-data')
            ->assertSuccessful();
    }

    public function test_registration_data_defaults_to_false_when_flag_is_absent(): void
    {
        $this->mock(GoogleSheetsService::class)
            ->shouldReceive('importSheet')
            ->once()
            ->with('sheet-id', 'Abertura', false, 1, null, false)
            ->andReturn(2);

        $this->artisan('app:import-google-sheets sheet-id --aba=Abertura --user-id=1')
            ->assertSuccessful();
    }

    public function test_sync_budget_is_called_for_orcamento_tab(): void
    {
        $this->mock(GoogleSheetsService::class)
            ->shouldReceive('syncBudget')
            ->once()
            ->with('sheet-id', 'Orçamento', 1)
            ->andReturn(3);

        $this->artisan('app:import-google-sheets sheet-id --aba=Orçamento --user-id=1')
            ->assertSuccessful();
    }
}
