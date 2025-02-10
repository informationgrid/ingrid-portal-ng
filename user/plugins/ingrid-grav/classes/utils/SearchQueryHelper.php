<?php

namespace Grav\Plugin;

class SearchQueryHelper
{

    private static array $replaces = [
        'iplugs:"' => 'iPlugId:"',
        ' cache:off' => '',
    ];

    public static function replaceInGridQuery(string &$query): void
    {
        foreach (self::$replaces as $key => $value) {
            $query = str_replace($key, $value, $query);
        }
    }
}