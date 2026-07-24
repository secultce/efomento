<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum CearaMacroregion: string
{
    case CARIRI = 'Cariri';
    case CENTRO_SUL = 'Centro Sul';
    case GRANDE_FORTALEZA = 'Grande Fortaleza';
    case LITORAL_LESTE = 'Litoral Leste';
    case LITORAL_NORTE = 'Litoral Norte';
    case LITORAL_OESTE_VALE_DO_CURU = 'Litoral Oeste / Vale do Curu';
    case MACICO_DE_BATURITE = 'Maciço de Baturité';
    case SERRA_DE_IBIAPABA = 'Serra de Ibiapaba';
    case SERTAO_CENTRAL = 'Sertão Central';
    case SERTAO_DE_CANINDE = 'Sertão de Canindé';
    case SERTAO_DE_SOBRAL = 'Sertão de Sobral';
    case SERTAO_DE_CRATEUS = 'Sertão de Crateús';
    case SERTAO_DOS_INHAMUNS = 'Sertão dos Inhamuns';
    case VALE_DO_JAGUARIBE = 'Vale do Jaguaribe';

    private const MUNICIPALITIES = [
        'Cariri' => [
            'Abaiara',
            'Altaneira',
            'Antonina do Norte',
            'Araripe',
            'Assaré',
            'Aurora',
            'Barbalha',
            'Barro',
            'Brejo Santo',
            'Campos Sales',
            'Caririaçu',
            'Crato',
            'Farias Brito',
            'Granjeiro',
            'Jardim',
            'Jati',
            'Juazeiro do Norte',
            'Lavras da Mangabeira',
            'Mauriti',
            'Milagres',
            'Missão Velha',
            'Nova Olinda',
            'Penaforte',
            'Porteiras',
            'Potengi',
            'Salitre',
            'Santana do Cariri',
            'Tarrafas',
            'Várzea Alegre',
        ],
        'Centro Sul' => [
            'Acopiara',
            'Baixio',
            'Cariús',
            'Catarina',
            'Cedro',
            'Icó',
            'Iguatu',
            'Ipaumirim',
            'Jucás',
            'Orós',
            'Quixelô',
            'Saboeiro',
            'Umari',
        ],
        'Grande Fortaleza' => [
            'Aquiraz',
            'Cascavel',
            'Caucaia',
            'Chorozinho',
            'Eusébio',
            'Fortaleza',
            'Guaiúba',
            'Horizonte',
            'Itaitinga',
            'Maracanaú',
            'Maranguape',
            'Pacajus',
            'Pacatuba',
            'Paracuru',
            'Paraipaba',
            'Pindoretama',
            'São Gonçalo do Amarante',
            'São Luís do Curu',
            'Trairi',
        ],
        'Litoral Leste' => [
            'Aracati',
            'Beberibe',
            'Fortim',
            'Icapuí',
            'Itaiçaba',
            'Jaguaruana',
        ],
        'Litoral Norte' => [
            'Acaraú',
            'Barroquinha',
            'Bela Cruz',
            'Camocim',
            'Chaval',
            'Cruz',
            'Granja',
            'Itarema',
            'Jijoca de Jericoacoara',
            'Marco',
            'Martinópole',
            'Morrinhos',
            'Uruoca',
        ],
        'Litoral Oeste / Vale do Curu' => [
            'Amontada',
            'Apuiarés',
            'General Sampaio',
            'Irauçuba',
            'Itapajé',
            'Itapipoca',
            'Miraíma',
            'Pentecoste',
            'Tejuçuoca',
            'Tururu',
            'Umirim',
            'Uruburetama',
        ],
        'Maciço de Baturité' => [
            'Acarape',
            'Aracoiaba',
            'Aratuba',
            'Barreira',
            'Baturité',
            'Capistrano',
            'Guaramiranga',
            'Itapiúna',
            'Mulungu',
            'Ocara',
            'Pacoti',
            'Palmácia',
            'Redenção',
        ],
        'Serra de Ibiapaba' => [
            'Carnaubal',
            'Croatá',
            'Guaraciaba do Norte',
            'Ibiapina',
            'Ipu',
            'São Benedito',
            'Tianguá',
            'Ubajara',
            'Viçosa do Ceará',
        ],
        'Sertão Central' => [
            'Banabuiú',
            'Choró',
            'Deputado Irapuan Pinheiro',
            'Ibaretama',
            'Ibicuitinga',
            'Milhã',
            'Mombaça',
            'Pedra Branca',
            'Piquet Carneiro',
            'Quixadá',
            'Quixeramobim',
            'Senador Pompeu',
            'Solonópole',
        ],
        'Sertão de Canindé' => [
            'Boa Viagem',
            'Canindé',
            'Caridade',
            'Itatira',
            'Madalena',
            'Paramoti',
        ],
        'Sertão de Sobral' => [
            'Alcântaras',
            'Cariré',
            'Coreaú',
            'Forquilha',
            'Frecheirinha',
            'Graça',
            'Groaíras',
            'Massapê',
            'Meruoca',
            'Moraújo',
            'Mucambo',
            'Pacujá',
            'Pires Ferreira',
            'Reriutaba',
            'Santana do Acaraú',
            'Senador Sá',
            'Sobral',
            'Varjota',
        ],
        'Sertão de Crateús' => [
            'Ararendá',
            'Catunda',
            'Crateús',
            'Hidrolândia',
            'Independência',
            'Ipaporanga',
            'Ipueiras',
            'Monsenhor Tabosa',
            'Nova Russas',
            'Novo Oriente',
            'Poranga',
            'Santa Quitéria',
            'Tamboril',
        ],
        'Sertão dos Inhamuns' => [
            'Aiuaba',
            'Arneiroz',
            'Parambu',
            'Quiterianópolis',
            'Tauá',
        ],
        'Vale do Jaguaribe' => [
            'Alto Santo',
            'Ererê',
            'Iracema',
            'Jaguaretama',
            'Jaguaribara',
            'Jaguaribe',
            'Limoeiro do Norte',
            'Morada Nova',
            'Palhano',
            'Pereiro',
            'Potiretama',
            'Quixeré',
            'Russas',
            'São João do Jaguaribe',
            'Tabuleiro do Norte',
        ],
    ];

    public static function forCity(?string $city): ?self
    {
        if ($city === null || trim($city) === '') {
            return null;
        }

        return self::macroregionByMunicipality()[self::normalize($city)] ?? null;
    }

    private static function macroregionByMunicipality(): array
    {
        static $macroregionByMunicipality = null;

        if ($macroregionByMunicipality !== null) {
            return $macroregionByMunicipality;
        }

        $macroregionByMunicipality = [];

        foreach (self::MUNICIPALITIES as $macroregion => $municipalities) {
            foreach ($municipalities as $municipality) {
                $macroregionByMunicipality[self::normalize($municipality)] = self::from($macroregion);
            }
        }

        return $macroregionByMunicipality;
    }

    private static function normalize(string $city): string
    {
        return (string) Str::of($city)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }
}
