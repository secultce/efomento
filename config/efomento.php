<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disco de armazenamento de arquivos
    |--------------------------------------------------------------------------
    |
    | Define qual disco do Laravel Storage será usado para upload de arquivos.
    | Troque FILE_DISK no .env para migrar para outro driver (ex: spaces, s3)
    | sem alterar nenhuma linha de código.
    |
    | Discos disponíveis: local, public, s3, spaces (configurar em filesystems.php)
    |
    */

    'file_disk' => env('FILE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Tamanho máximo de upload (KB)
    |--------------------------------------------------------------------------
    */

    'file_max_size' => env('FILE_MAX_SIZE', 20480),

    /*
    |--------------------------------------------------------------------------
    | Mapa Cultural
    |--------------------------------------------------------------------------
    */

    'mapas_domain' => env('MAPAS_DOMAIN'),
    'mapas_token' => env('MAPAS_TOKEN'),
    'mapas_seal' => env('MAPAS_SEAL'),

    'http_timeout' => env('MAPAS_HTTP_TIMEOUT', 10),
    'http_retries' => env('MAPAS_HTTP_RETRIES', 3),
    'http_retry_sleep_ms' => env('MAPAS_HTTP_RETRY_SLEEP_MS', 1000),

    'mapas_rate_limit_per_minute' => env('MAPAS_RATE_LIMIT_PER_MINUTE', 60),

    'registration_detail_chunk' => env('MAPAS_REGISTRATION_DETAIL_CHUNK', 100),

    'file_download_timeout' => env('MAPAS_FILE_DOWNLOAD_TIMEOUT', 60),

];
