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

];
