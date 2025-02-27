<?php

namespace Grav\Plugin;

class LanguageHelper
{

    private static array $languageISO639_2ToIGCCode = [
        "deu" => 150,
        "ger" => 150,
        "eng" => 123,
        "bul" => 65,
        "cze" => 101,
        "dan" => 103,
        "spa" => 401,
        "fin" => 134,
        "fre" => 137,
        "gre" => 164,
        "hun" => 183,
        "dut" => 116,
        "pol" => 346,
        "por" => 348,
        "rum" => 360,
        "slo" => 385,
        "slv" => 386,
        "ita" => 202,
        "est" => 126,
        "lav" => 247,
        "lit" => 251,
        "nno" => 312,
        "rus" => 363,
        "swe" => 413,
        "mlt" => 284,
        "wen" => 467,
        "hsb" => 182,
        "dsb" => 113,
        "fry" => 142,
        "nds" => 306,
    ];

    private static array $languageCodelist_de = [
        150 => "Deutsch",
        123 => "Englisch",
        65 => "Bulgarisch",
        101 => "Tschechisch",
        103 => "Dänisch",
        401 => "Spanisch",
        134 => "Finnisch",
        137 => "Französisch",
        164 => "Griechisch",
        183 => "Ungarisch",
        116 => "Niederländisch",
        346 => "Polnisch",
        348 => "Portugiesisch",
        360 => "Rumänisch",
        385 => "Slowakisch",
        386 => "Slowenisch",
        202 => "Italienisch",
        126 => "Estnisch",
        247 => "Lettisch",
        251 => "Litauisch",
        312 => "Norwegisch",
        363 => "Russisch",
        413 => "Schwedisch",
        284 => "Maltesisch",
        467 => "Sorbisch",
        182 => "Obersorbisch",
        113 => "Niedersorbisch",
        142 => "Friesisch",
        306 => "Niedersächsisch",
    ];

    private static array $languageCodelist_en = [
        150 => "German",
        123 => "English",
        65 => "Bulgarian",
        101 => "Czech",
        103 => "Danish",
        401 => "Spanish",
        134 => "Finish",
        137 => "French",
        164 => "Greek",
        183 => "Hungarian",
        116 => "Dutch",
        346 => "Polish",
        348 => "Portuguese",
        360 => "Romanian",
        385 => "Slovakian",
        386 => "Slovenian",
        202 => "Italian",
        126 => "Estonian",
        247 => "Latvian",
        251 => "Lithuanian",
        312 => "Norwegian",
        363 => "Russian",
        413 => "Swedish",
        284 => "Maltese",
        467 => "Sorbian",
        182 => "Upper Sorbian",
        113 => "Lower Sorbian",
        142 => "Western Frisian",
        306 => "Low Saxon",
    ];

    public static function getNameFromIso639_2(string $isoCode, string $lang): string
    {
        $name = $isoCode;
        if (array_key_exists($isoCode, self::$languageISO639_2ToIGCCode)) {
            $igeCode = self::$languageISO639_2ToIGCCode[$isoCode];
            if ($igeCode) {
                if ($lang == 'en') {
                    $name = self::$languageCodelist_en[$igeCode];
                } else {
                    $name = self::$languageCodelist_de[$igeCode];
                }
            }
        }
        return $name;
    }

    public static function getNamesFromIso639_2(?array $isoCodes, string $lang): ?array
    {
        if ($isoCodes) {
            $array = [];
            foreach ($isoCodes as $isoCode) {
                $array[] = self::getNameFromIso639_2($isoCode, $lang);
            }
            return $array;
        }
        return null;
    }
}