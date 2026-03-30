<?php

namespace App\Services\Documents;

class DocumentTypeRegistry
{
    private static array $map = [
        'term+formalization' => [
            'label'          => 'Termo de Execução Cultural',
            'requires_sign'  => true,
            'requires_legal' => false,
        ],
        'extract+formalization' => [
            'label'          => 'Extrato do Termo',
            'requires_sign'  => false,
            'requires_legal' => false,
        ],
        'juridical_opinion+juridical' => [
            'label'          => 'Parecer Jurídico',
            'requires_sign'  => true,
            'requires_legal' => true,
        ],
        'dispatch+juridical' => [
            'label'          => 'Despacho para Parecer Jurídico',
            'requires_sign'  => false,
            'requires_legal' => false,
        ],
    ];

    public static function resolve(string $type, string $phase): array
    {
        $key = "{$type}+{$phase}";

        if (!isset(self::$map[$key])) {
            throw new \InvalidArgumentException(
                "Combinação de tipo e fase inválida: tipo={$type}, fase={$phase}."
            );
        }

        return self::$map[$key];
    }
}
