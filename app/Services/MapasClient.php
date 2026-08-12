<?php

namespace App\Services;

use App\Exceptions\Integration\ExternalServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class MapasClient
{
    private const SERVICE_NAME = 'Mapas Cultural';

    private const AGENT_SELECT_FIELDS = [
        'id',
        'name',
        'cpf',
        'genero',
        'orientacaoSexual',
        'raca',
        'escolaridade',
        'pessoaDeficiente',
        'telefone1',
        'telefone2',
        'emailPublico',
        'emailPrivado',
        'dataDeNascimento',
        'En_Nome_Logradouro',
        'En_Num',
        'En_Complemento',
        'En_CEP',
        'En_Bairro',
        'En_Municipio',
        'En_Estado',
    ];

    public function publishedNotices(): Collection
    {
        return collect($this->get('/api/opportunity/find', [
            '@select' => 'id,name,singleUrl',
            '@seals' => config('efomento.mapas_seal'),
            'publish_site' => 'EQ(Sim)',
        ], authenticated: false));
    }

    public function monitoringOpportunities(): Collection
    {
        $seal = config('efomento.mapas_monitoring_seal');
        if (blank($seal)) {
            return collect();
        }

        return collect($this->get('/api/opportunity/find', [
            '@select' => 'id,name,singleUrl,status',
            '@seals' => $seal,
            '@permissions' => 'view',
            'status' => 'EQ(-1)',
        ]));
    }

    public function selectedRegistrations(int $noticeId): Collection
    {
        return collect($this->get('/api/opportunity/findRegistrations', [
            '@select' => 'id,createTimestamp,sentTimestamp',
            '@opportunity' => $noticeId,
            'status' => 'EQ(10)',
        ]));
    }

    public function monitoringRegistrations(int $opportunityId): Collection
    {
        return collect($this->get('/api/opportunity/findRegistrations', [
            '@select' => 'id,number,createTimestamp,sentTimestamp',
            '@opportunity' => $opportunityId,
            'status' => 'EQ(1)',
        ]));
    }

    public function registrationDetails(int $registrationId): array
    {
        return $this->get("/registration/detalhes/{$registrationId}");
    }

    public function agentById(int $agentId): array
    {
        $key = "mapas:agent:{$agentId}";

        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        return Cache::lock("mapas:agent-lock:{$agentId}", 60)
            ->block(5, function () use ($key, $agentId) {
                $cached = Cache::get($key);

                if (is_array($cached)) {
                    return $cached;
                }

                $agent = $this->get('/api/agent/findOne', [
                    '@select' => implode(',', self::AGENT_SELECT_FIELDS),
                    'id' => "EQ({$agentId})",
                ]);

                Cache::put($key, $agent, now()->addHour());

                return $agent;
            });
    }

    private function get(
        string $path,
        array $query = [],
        bool $authenticated = true
    ): array {
        $response = $this->request($authenticated)->get($path, $query);

        if ($response->failed()) {
            throw ExternalServiceException::fromFailedResponse(
                self::SERVICE_NAME,
                sprintf('Erro na API Mapas [%s] %s', $response->status(), $path),
                ['path' => $path],
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw ExternalServiceException::fromFailedResponse(
                self::SERVICE_NAME,
                "Resposta inválida da API Mapas: {$path}",
                ['path' => $path],
            );
        }

        return $json;
    }

    private function request(bool $authenticated = true): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) config('efomento.mapas_domain'), '/'))
            ->acceptJson()
            ->timeout((int) config('efomento.http_timeout', 10))
            ->retry(
                (int) config('efomento.http_retries', 3),
                fn (int $attempt) => $attempt * (int) config('efomento.http_retry_sleep_ms', 1000),
                fn (Throwable $exception, PendingRequest $request) => $this->shouldRetry($exception),
                throw: false
            );

        if ($authenticated) {
            $request = $request->withHeaders([
                'authorization' => config('efomento.mapas_token'),
            ]);
        }

        return $request;
    }

    private function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            return in_array(
                $exception->response->status(),
                [408, 429, 500, 502, 503, 504],
                true
            );
        }

        return false;
    }

    public function downloadFileTo(string $url, string $absolutePath): array
    {
        $response = Http::withHeaders([
            'authorization' => config('efomento.mapas_token'),
            'Accept' => '*/*',
        ])
            ->timeout((int) config('efomento.file_download_timeout', 60))
            ->retry(
                (int) config('efomento.http_retries', 3),
                fn (int $attempt) => $attempt * (int) config('efomento.http_retry_sleep_ms', 1000),
                fn (Throwable $exception, PendingRequest $request) => $this->shouldRetry($exception),
                throw: false
            )
            ->sink($absolutePath)
            ->get($url);

        if ($response->failed()) {
            @unlink($absolutePath);

            throw ExternalServiceException::fromFailedResponse(
                self::SERVICE_NAME,
                sprintf(
                    'Erro ao baixar arquivo do Mapas [%s]: %s',
                    $response->status(),
                    $this->redactUrl($url)
                ),
                ['url' => $this->redactUrl($url)],
            );
        }

        $mimeType = $this->resolveDownloadedMimeType(
            $response->header('Content-Type'),
            $absolutePath
        );

        if ($mimeType === 'text/html') {
            @unlink($absolutePath);

            throw ExternalServiceException::fromFailedResponse(
                self::SERVICE_NAME,
                "Download inválido: o Mapas retornou HTML em vez de arquivo. URL: {$this->redactUrl($url)}",
                ['url' => $this->redactUrl($url)],
            );
        }

        return [
            'mime_type' => $mimeType,
            'size' => file_exists($absolutePath) ? filesize($absolutePath) : null,
        ];
    }

    private function redactUrl(string $url): string
    {
        return (string) str($url)->before('?');
    }

    private function resolveDownloadedMimeType(?string $contentType, string $absolutePath): string
    {
        $mimeType = $contentType
            ? trim(str($contentType)->before(';')->toString())
            : null;

        if ($mimeType && $mimeType !== 'application/octet-stream') {
            return $mimeType;
        }

        return mime_content_type($absolutePath) ?: 'application/octet-stream';
    }
}
