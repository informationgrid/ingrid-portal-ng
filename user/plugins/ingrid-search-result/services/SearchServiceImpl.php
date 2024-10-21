<?php

namespace Grav\Plugin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SearchServiceImpl implements SearchService
{

    private string $api;
    private int $hitsNum;
    private Client $client;
    private $log;
    private array $facet_config;


    function __construct($grav)
    {
        $this->facet_config = $grav['config']->get('plugins.ingrid-search-result.facet_config');

        $this->api = getenv('INGRID_API') ?? $grav['config']->get('plugins.ingrid-detail.api_url');
        $this->hitsNum = $grav['config']->get('plugins.ingrid-search-result.hits_num');
        $this->client = new Client(['base_uri' => $this->api]);
        $this->log = $grav['log'];
    }

    /**
     * @param string $query
     * @param int $page
     * @return SearchResult
     * @throws GuzzleException
     */
    public function getSearchResults(string $query, int $page, array $selectedFacets, $uri): SearchResult
    {
        $apiResponse = $this->client->request('POST', 'portal/search', [
            'body' => $this->transformQuery($query, $page, $selectedFacets)
        ]);
        $result = json_decode($apiResponse->getBody()->getContents());
        return new SearchResult(
            numOfHits: $result->totalHits ?? 0,
            numOfPages: $result->numOfPages ?? 0,
            numPage: $result->numPage ?? 0,
            hits: SearchResponseTransformerClassic::parseHits($result->hits ?? null),
            facets: SearchResponseTransformerClassic::parseAggregations((object)$result->aggregations, $this->facet_config, $uri),
        );
    }

    private function transformQuery($query, $page, array $selectedFacets): string
    {
        $result = ElasticsearchService::convertToQuery($query, $this->facet_config, $page, $this->hitsNum, $selectedFacets);
        $this->log->debug('Elasticsearch query: ' . $result);
        return $result;
    }

}
