<?php

namespace Tests\Feature;

use App\Exceptions\Integration\PartialSpreadsheetSyncException;
use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportGoogleSheetsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_tabs_use_shared_import_with_cli_defaults(): void
    {
        $this->mock(GoogleSheetsService::class)->shouldReceive('importAll')->once()
            ->with('sheet-id', 1, null, false)
            ->andReturn(['counts' => ['Abertura' => 2, 'Formalização' => 2, 'Orçamento' => 2, 'Pagamento' => 2], 'errors' => []]);

        $this->artisan('app:import-google-sheets sheet-id --user-id=1')->assertSuccessful();
    }

    public function test_all_tabs_return_failure_when_any_tab_failed(): void
    {
        $this->mock(GoogleSheetsService::class)->shouldReceive('importAll')->once()
            ->with('sheet-id', 1, 7, true)
            ->andReturn(['counts' => ['Abertura' => 2], 'errors' => ['Formalização' => 'Tab unavailable']]);

        $this->artisan('app:import-google-sheets sheet-id --user-id=1 --notice-id=7 --with-files')
            ->assertFailed();
    }

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

    public function test_single_tab_command_returns_failure_when_some_rows_fail(): void
    {
        $this->mock(GoogleSheetsService::class)->shouldReceive('syncBudget')->once()
            ->with('sheet-id', 'Orçamento', 1)
            ->andThrow(new PartialSpreadsheetSyncException(2, 1));

        $this->artisan('app:import-google-sheets sheet-id --aba=Orçamento --user-id=1')
            ->expectsOutput('  Erro: 1 registro(s) não foram gravados; 2 registro(s) processados.')
            ->assertFailed();
    }
}
