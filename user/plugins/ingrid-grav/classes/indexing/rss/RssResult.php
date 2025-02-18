<?php

namespace Grav\Plugin;

class RssResult
{
    public function __construct()
    {
    }

    public static function getResults(): array
    {
        $response = null;
        try {
            $response = file_get_contents('user-data://feeds/feeds.json');
        } catch (\Throwable $th) {
        }
        $result = json_decode($response, true);
        return $result['data'] ?? [];
    }

}
