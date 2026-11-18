<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Exceptions;
use Sentry\State\HubInterface;
use Tests\TestCase;

class SentryReportingTest extends TestCase
{
    public function test_dsn_prefers_sentry_laravel_dsn_over_sentry_dsn(): void
    {
        $config = $this->loadSentryConfig([
            'SENTRY_LARAVEL_DSN' => 'https://laravel@sentry.example.com/1',
            'SENTRY_DSN' => 'https://generic@sentry.example.com/2',
        ]);

        $this->assertSame('https://laravel@sentry.example.com/1', $config['dsn']);
    }

    public function test_dsn_falls_back_to_sentry_dsn_when_laravel_dsn_is_missing(): void
    {
        $config = $this->loadSentryConfig([
            'SENTRY_DSN' => 'https://generic@sentry.example.com/2',
        ]);

        $this->assertSame('https://generic@sentry.example.com/2', $config['dsn']);
    }

    public function test_traces_sample_rate_defaults_to_zero_when_env_is_missing(): void
    {
        $config = $this->loadSentryConfig([]);

        $this->assertSame(0.0, $config['traces_sample_rate']);
    }

    public function test_traces_sample_rate_is_cast_to_float_from_env(): void
    {
        $config = $this->loadSentryConfig(['SENTRY_TRACES_SAMPLE_RATE' => '0.25']);

        $this->assertSame(0.25, $config['traces_sample_rate']);
    }

    /**
     * Reavalia o config/sentry.php com as variáveis de ambiente informadas,
     * exercitando a lógica real de resolução (fallbacks e casts) do arquivo.
     */
    private function loadSentryConfig(array $env): array
    {
        $keys = ['SENTRY_LARAVEL_DSN', 'SENTRY_DSN', 'SENTRY_TRACES_SAMPLE_RATE'];

        $backup = [];
        foreach ($keys as $key) {
            $backup[$key] = [
                array_key_exists($key, $_ENV) ? $_ENV[$key] : null,
                array_key_exists($key, $_SERVER) ? $_SERVER[$key] : null,
            ];
            unset($_ENV[$key], $_SERVER[$key]);
        }

        foreach ($env as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        try {
            return require base_path('config/sentry.php');
        } finally {
            foreach ($backup as $key => [$envValue, $serverValue]) {
                unset($_ENV[$key], $_SERVER[$key]);

                if ($envValue !== null) {
                    $_ENV[$key] = $envValue;
                }
                if ($serverValue !== null) {
                    $_SERVER[$key] = $serverValue;
                }
            }
        }
    }

    public function test_client_does_not_send_events_when_dsn_is_empty(): void
    {
        config(['sentry.dsn' => null]);

        $client = app(HubInterface::class)->getClient();

        $this->assertNotNull($client);
        $this->assertNull($client->getOptions()->getDsn());
    }

    public function test_invalid_argument_exception_still_returns_422_when_report_is_wired(): void
    {
        Exceptions::fake();

        $this->app['router']->post('/__sentry-test/invalid-argument', function () {
            throw new \InvalidArgumentException('Falha de teste.');
        })->middleware('web');

        $response = $this->postJson('/__sentry-test/invalid-argument');

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Falha de teste.']);

        Exceptions::assertReported(function (\InvalidArgumentException $e): bool {
            return $e->getMessage() === 'Falha de teste.';
        });
    }
}
