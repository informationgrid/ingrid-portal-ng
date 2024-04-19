<?php

namespace Grav\Plugin\IngridRss;

class RssIndex
{
    public function __construct()
    {
    }

    public static function indexJob($config): object
    {
        echo "<script>console.log('RSS reader'" . $config .");</script>";
        $response = file_get_contents('user-data://test/rss/result.json');
        $result = json_decode($response, true) ?? [];
        $output = new RssResult();
        $output->setLastExecution($result['lastExecution'] ?? null);
        $output->setHits($result['hits'] ?? []);
        return $output;
    }

}
