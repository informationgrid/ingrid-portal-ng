<?php

namespace Grav\Plugin;

use Grav\Common\GPM\Response;

class SearchResult
{
    var $numOfHits;
    var $numOfPages;
    var $numPage;
    var $hits;
    var $facets;

    public function __construct() {}

    public static function getResults(string $api, ?string $query, ?string $hitsNum): object
    {

        $host = $api;
        $host .= "q=".urlencode($query);
        //$response = Response::get($host);
        $response = file_get_contents('user-data://test/search/result.json');
        $result = json_decode($response, true) ?? [];
        $output = new SearchResult();
        $output->setNumOfHits($result['numOfHits'] ?? 0);
        $output->setNumOfPages($result['numOfPages'] ?? 0);
        $output->setNumPage($result['numPage'] ?? 0);
        $output->setHits(SearchResultHit::parseHits($result['hits'] ?? null));
        return $output;
    }

    public function getNumOfHits()
    {
        return $this->numOfHits;
    }
    
    public function setNumOfHits($numOfHits)
    {
        $this->numOfHits = $numOfHits;
    }
    
    public function getNumOfPages()
    {
        return $this->numOfPages;
    }
    
    public function setNumOfPages($numOfPages)
    {
        $this->numOfPages = $numOfPages;
    }

    public function getNumPage()
    {
        return $this->numPage;
    }
    
    public function setNumPage($numPage)
    {
        $this->numPage = $numPage;
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
