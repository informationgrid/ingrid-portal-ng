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

    public static function transformColonQuery(string &$query): void
    {
        $tmpQuery = '';
        $splits = explode(' ', trim($query));
        foreach ($splits as $split) {
            if (str_contains($split, ':')) {
                $fieldQuery = $split;
                $termQuery = str_replace(':', '\:', $split);
                if (str_ends_with($fieldQuery, ':')) {
                    $fieldQuery = $fieldQuery . '\'\'';
                }
                $tmpQuery .= '((' . $fieldQuery . ') OR (' . $termQuery . '))';
            } else
                $tmpQuery .= empty($tmpQuery) ? $split : ' ' . $split;

        }
        if (!empty($tmpQuery)) {
            $query = '(' . $tmpQuery . ')';
        }
    }

}