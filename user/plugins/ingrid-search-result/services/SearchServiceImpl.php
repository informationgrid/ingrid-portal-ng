<?php

namespace Grav\Plugin;

use GuzzleHttp\Client;

class SearchServiceImpl implements SearchService
{

    private $api;
    private $hitsNum;
    private $client;

    function __construct($grav)
    {
        $this->api = $grav['config']->get('plugins.ingrid-search-result.api_url');
        $this->hitsNum = $grav['config']->get('plugins.ingrid-search-result.hits_num');
        $this->client = new Client(['base_uri' => $this->api]);
    }

    public function getSearchResults($query): SearchResult
    {
        $apiResponse = $this->client->request('POST', 'portal/search', []);
        $result = json_decode($apiResponse->getBody()->getContents());
        $output = new SearchResult();
        $output->setNumOfHits($result->totalHits ?? 0);
        $output->setNumOfPages($result->numOfPages ?? 0);
        $output->setNumPage($result->numPage ?? 0);
        $output->setHits(SearchResponseTransformer::parseHits($result->hits ?? null));
        return $output;
    }

}