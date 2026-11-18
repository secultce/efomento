<?php

namespace Tests\Unit;

use App\Enums\CearaMacroregion;
use App\Models\ProfileSnapshot;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CearaMacroregionTest extends TestCase
{
    #[DataProvider('municipalities')]
    public function test_returns_the_macroregion_for_a_municipality(
        string $municipality,
        CearaMacroregion $macroregion
    ): void {
        $this->assertSame($macroregion, CearaMacroregion::forCity($municipality));
    }

    public static function municipalities(): array
    {
        return [
            ['Crato', CearaMacroregion::CARIRI],
            ['Iguatu', CearaMacroregion::CENTRO_SUL],
            ['Fortaleza', CearaMacroregion::GRANDE_FORTALEZA],
            ['Aracati', CearaMacroregion::LITORAL_LESTE],
            ['Camocim', CearaMacroregion::LITORAL_NORTE],
            ['Itapipoca', CearaMacroregion::LITORAL_OESTE_VALE_DO_CURU],
            ['General Sampaio', CearaMacroregion::LITORAL_OESTE_VALE_DO_CURU],
            ['Baturité', CearaMacroregion::MACICO_DE_BATURITE],
            ['Tianguá', CearaMacroregion::SERRA_DE_IBIAPABA],
            ['Quixadá', CearaMacroregion::SERTAO_CENTRAL],
            ['Canindé', CearaMacroregion::SERTAO_DE_CANINDE],
            ['Boa Viagem', CearaMacroregion::SERTAO_DE_CANINDE],
            ['Sobral', CearaMacroregion::SERTAO_DE_SOBRAL],
            ['Crateús', CearaMacroregion::SERTAO_DE_CRATEUS],
            ['Tauá', CearaMacroregion::SERTAO_DOS_INHAMUNS],
            ['Jaguaribe', CearaMacroregion::VALE_DO_JAGUARIBE],
        ];
    }

    public function test_normalizes_case_whitespace_and_accents(): void
    {
        $this->assertSame(
            CearaMacroregion::GRANDE_FORTALEZA,
            CearaMacroregion::forCity('  SAO GONCALO DO AMARANTE  ')
        );
    }

    public function test_returns_null_for_an_unknown_or_empty_city(): void
    {
        $this->assertNull(CearaMacroregion::forCity('Recife'));
        $this->assertNull(CearaMacroregion::forCity(''));
        $this->assertNull(CearaMacroregion::forCity(null));
    }

    public function test_mapping_contains_all_184_ceara_municipalities(): void
    {
        $regions = (new ReflectionClass(CearaMacroregion::class))
            ->getReflectionConstant('MUNICIPALITIES')
            ->getValue();

        $this->assertCount(14, $regions);
        $this->assertCount(184, array_merge(...array_values($regions)));
    }

    public function test_uses_the_official_pdf_macroregion_names(): void
    {
        $this->assertSame('Serra de Ibiapaba', CearaMacroregion::SERRA_DE_IBIAPABA->value);
        $this->assertSame('Sertão de Crateús', CearaMacroregion::SERTAO_DE_CRATEUS->value);
    }

    public function test_profile_snapshot_serialization_includes_the_macroregion(): void
    {
        $snapshot = new ProfileSnapshot([
            'city' => 'JUAZEIRO DO NORTE',
        ]);

        $this->assertSame('Cariri', $snapshot->toArray()['macroregion']);
    }
}
