<?php

namespace Grav\Plugin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SearchServiceImpl implements SearchService
{

    private string $api;
    private int $hitsNum;
    private Client $client;

    function __construct($grav)
    {
        $this->api = getenv('INGRID_API');
        $this->hitsNum = $grav['config']->get('plugins.ingrid-search-result.hits_num');
        $this->client = new Client(['base_uri' => $this->api]);
    }

    /**
     * @throws GuzzleException
     */
    public function getSearchResults($query): SearchResult
    {
        $apiResponse = $this->client->request('POST', 'portal/search', [
            'body' => ElasticsearchService::convertToQuery($query)
        ]);
        $result = json_decode($apiResponse->getBody()->getContents());
        return new SearchResult(
            numOfHits: $result->totalHits ?? 0,
            numOfPages: $result->numOfPages ?? 0,
            numPage: $result->numPage ?? 0,
            hits: SearchResponseTransformerClassic::parseHits($result->hits ?? null)
        );
    }

}