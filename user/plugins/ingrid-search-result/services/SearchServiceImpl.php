<?php

namespace Grav\Plugin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SearchServiceImpl implements SearchService
{

    private string $api;
    private $facets;
    private int $hitsNum;
    private Client $client;
    private $log;
    private array $facet_config;


    function __construct($grav)
    {
        $this->facet_config = $grav['config']->get('plugins.ingrid-search-result.facet_config');

        $this->api = getenv('INGRID_API');
        $this->hitsNum = $grav['config']->get('plugins.ingrid-search-result.hits_num');
        $this->facets = $this->mapFacets($this->facet_config);
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
            hits: SearchResponseTransformerClassic::parseHits($result->hits ?? null),
            facets: SearchResponseTransformerClassic::parseAggregations((object)$result->aggregations, $this->facet_config),
        );
    }

    private function transformQuery($query, $page): string
    {
        $result = ElasticsearchService::convertToQuery($query, $this->facets, $page, $this->hitsNum);
        $this->log->debug('Elasticsearch query: ' . $result);
        return $result;
    }

    /**
     * @param FacetConfig[] $facets
     * @return object
     */
    private function mapFacets(array $facets): object
    {
        $result = array();
        foreach ($facets as $facet) {
            if (property_exists((object)$facet, 'queries')) {
                foreach ($facet['queries'] as $subfacet_id => $subfacet_value) {
                    $result[$subfacet_id] = $subfacet_value;
                }
            } else {
                $result[$facet['id']] = $facet['query'];
            }
        }
        return (object)$result;
    }

}
