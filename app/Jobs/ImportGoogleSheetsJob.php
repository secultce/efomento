<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\GoogleSheetsService;
use App\Support\Notify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportGoogleSheetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 240;

    public bool $failOnTimeout = true;

    public function __construct(
        public string $spreadsheetId,
        public int $userId,
        public ?int $fallbackNoticeId,
        public int $triggeredByUserId,
    ) {
        $this->onQueue('default');
    }

    public function handle(GoogleSheetsService $service, Notify $notify): void
    {
        $result = $service->importAll($this->spreadsheetId, $this->userId, $this->fallbackNoticeId, true, true);
        $user = User::find($this->triggeredByUserId);

        if (! $user) {
            return;
        }

        $summary = collect($result['counts'])->map(fn (int $count, string $sheet) => "{$sheet}: {$count}")->implode('; ');
        $meta = ['spreadsheet_id' => $this->spreadsheetId, 'counts' => $result['counts'], 'failed_sheets' => array_keys($result['errors'])];

        if ($result['errors'] !== []) {
            $notify->user($user)->error(
                'A sincronização da planilha terminou com erros nas abas: '.implode(', ', array_keys($result['errors'])).'. '
                .($summary ? "Registros processados — {$summary}. " : '')
                .'Verifique o acesso público e os dados da planilha antes de tentar novamente.',
                'Erro na sincronização da planilha',
                $meta,
            );

            return;
        }

        $notify->user($user)->success(
            "Planilha sincronizada. Registros processados — {$summary}. Arquivos e fichas de inscrição continuarão sendo processados em segundo plano.",
            'Planilha sincronizada',
            $meta,
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('spreadsheet.import.failed', ['spreadsheet_id' => $this->spreadsheetId, 'message' => $exception->getMessage()]);

        if ($user = User::find($this->triggeredByUserId)) {
            app(Notify::class)->user($user)->error(
                'Não foi possível concluir a sincronização da planilha. Alguns registros podem ter sido importados. Verifique a planilha e tente novamente.',
                'Erro na sincronização da planilha',
                ['spreadsheet_id' => $this->spreadsheetId],
            );
        }
    }
}
