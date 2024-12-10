<?php

namespace Grav\Plugin;

class SearchQueryHelper
{

    private static array $replaces = [
        'iplugs:"' => 'iPlugId:"',
    ];

    public static function replaceInGridQuery(string &$query): void
    {
        foreach (self::$replaces as $key => $value) {
            $query = str_replace($key, $value, $query);
        }
    }
}