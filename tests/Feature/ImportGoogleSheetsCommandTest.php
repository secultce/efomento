<?php

namespace Tests\Feature;

use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportGoogleSheetsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_sheet_is_called_for_abertura_tab(): void
    {
        $this->mock(GoogleSheetsService::class)
            ->shouldReceive('importSheet')
            ->once()
            ->with('sheet-id', 'Abertura', false, 1, null)
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
