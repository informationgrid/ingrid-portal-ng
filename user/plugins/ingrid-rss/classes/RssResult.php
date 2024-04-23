<?php

namespace Grav\Plugin\IngridRss;

class RssResult
{
    var $lastExecution;
    var $hits;

    public function __construct()
    {
    }

    public static function getResults(): object
    {

        $response = file_get_contents('user-data://test/rss/result.json');
        $result = json_decode($response, true) ?? [];
        $output = new RssResult();
        $output->setLastExecution($result['lastExecution'] ?? null);
        $output->setHits($result['hits'] ?? []);
        return $output;
    }

    public function getLastExecution()
    {
        return $this->lastExecution;
    }
    
    public function setLastExecution($lastExecution)
    {
        $this->lastExecution = $lastExecution;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function setHits($hits)
    {
        $this->hits = $hits;
    }
}
