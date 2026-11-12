<?php

namespace Tests\Feature;

use App\Exceptions\Integration\PartialSpreadsheetSyncException;
use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportGoogleSheetsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_tabs_use_shared_import_while_preserving_cli_defaults(): void
    {
        $this->mock(GoogleSheetsService::class)->shouldReceive('importAll')->once()
            ->with('sheet-id', 1, null, false, false)
            ->andReturn(['counts' => ['Abertura' => 2, 'Formalização' => 2, 'Orçamento' => 2, 'Pagamento' => 2], 'errors' => []]);

        $this->artisan('app:import-google-sheets sheet-id --user-id=1')->assertSuccessful();
    }

    public function test_all_tabs_return_failure_when_any_tab_failed(): void
    {
        $this->mock(GoogleSheetsService::class)->shouldReceive('importAll')->once()
            ->with('sheet-id', 1, 7, true, true)
            ->andReturn(['counts' => ['Abertura' => 2], 'errors' => ['Formalização' => 'Tab unavailable']]);

        $this->artisan('app:import-google-sheets sheet-id --user-id=1 --notice-id=7 --with-files --with-registration-data')
            ->assertFailed();
    }

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
