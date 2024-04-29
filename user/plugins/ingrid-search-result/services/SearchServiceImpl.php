<?php

namespace Grav\Plugin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SearchServiceImpl implements SearchService
{

    private string $api;
    private array $facets;
    private int $hitsNum;
    private Client $client;
    private $log;

    function __construct($grav)
    {
        $this->api = getenv('INGRID_API');
        $this->hitsNum = $grav['config']->get('plugins.ingrid-search-result.hits_num');
        $this->facets = $grav['config']->get('plugins.ingrid-search-result.facets');
        $this->client = new Client(['base_uri' => $this->api]);
        $this->log = $grav['log'];
    }

    /**
     * @param string $query
     * @param int $page
     * @return SearchResult
     * @throws GuzzleException
     */
    public function getSearchResults(string $query, int $page): SearchResult
    {
        $apiResponse = $this->client->request('POST', 'portal/search', [
            'body' => $this->transformQuery($query, $page)
        ]);
        $result = json_decode($apiResponse->getBody()->getContents());
        return new SearchResult(
            numOfHits: $result->totalHits ?? 0,
            numOfPages: $result->numOfPages ?? 0,
            numPage: $result->numPage ?? 0,
            hits: SearchResponseTransformerClassic::parseHits($result->hits ?? null)
        );
    }

    /**
     * @param string $query
     * @param int $page
     * @return string
     */
    public function transformQuery(string $query, int $page): string
    {
        $result = ElasticsearchService::convertToQuery($query, $this->facets, $page, $this->hitsNum);
        $this->log->debug('Elasticsearch query: ' . $result);
        return $result;
    }

}