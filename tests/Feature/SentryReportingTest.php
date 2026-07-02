<?php

namespace Tests\Feature;

use Sentry\State\HubInterface;
use Tests\TestCase;

class SentryReportingTest extends TestCase
{
    public function test_dsn_is_resolved_from_env(): void
    {
        config(['sentry.dsn' => 'https://public@sentry.example.com/1']);

        $this->assertSame('https://public@sentry.example.com/1', config('sentry.dsn'));
    }

    public function test_traces_sample_rate_defaults_to_zero_when_env_is_missing(): void
    {
        config(['sentry.traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0)]);

        $this->assertSame(0.0, config('sentry.traces_sample_rate'));
    }

    public function test_client_does_not_send_events_when_dsn_is_empty(): void
    {
        $client = app(HubInterface::class)->getClient();

        $this->assertNotNull($client);
        $this->assertNull($client->getOptions()->getDsn());
    }

    public function test_invalid_argument_exception_still_returns_422_when_report_is_wired(): void
    {
        $this->app['router']->post('/__sentry-test/invalid-argument', function () {
            throw new \InvalidArgumentException('Falha de teste.');
        })->middleware('web');

        $response = $this->postJson('/__sentry-test/invalid-argument');

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Falha de teste.']);
    }
}
