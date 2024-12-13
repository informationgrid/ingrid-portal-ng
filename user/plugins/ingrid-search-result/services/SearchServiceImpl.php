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
        // Plugin config
        $hitsNum = $grav['config']->get('plugins.ingrid-search-result.hits_num');
        $facetConfig = $grav['config']->get('plugins.ingrid-search-result.facet_config');

        // Theme config
        $theme = $grav['config']->get('system.pages.theme');
        $facetConfig = $grav['config']->get('themes.' . $theme . '.hit_search.facet_config') ?? $facetConfig;
        $hitsNum = $grav['config']->get('themes.' . $theme . '.hit_search.hits_num') ?? $hitsNum;

        $this->facet_config = $facetConfig;

        $this->api = getenv('INGRID_API') !== false ?
            getenv('INGRID_API') : $grav['config']->get('plugins.ingrid-detail.ingrid_api_url');
        $this->hitsNum = $hitsNum;
        $this->client = new Client(['base_uri' => $this->api]);
        $this->log = $grav['log'];
    }


    /**
     * @param string $query
     * @param int $page
     * @return SearchResult
     * @throws GuzzleException
     */
    public function getSearchResults(string $query, int $page, array $selectedFacets, $uri, string $lang): null|SearchResult
    {
        try {
            $apiResponse = $this->client->request('POST', 'portal/search', [
                'body' => $this->transformQuery($query, $page - 1, $selectedFacets)
            ]);
            $result = json_decode($apiResponse->getBody()->getContents());
            $totalHits = $result->totalHits ?? 0;
            $numOfPages = ceil($result->totalHits / $this->hitsNum) ?? 0;
            return new SearchResult(
                numOfHits: intval($totalHits),
                numOfPages: intval($numOfPages),
                numPage: $page,
                listOfPages: $this->getPageRanges($page, $numOfPages),
                hits: SearchResponseTransformerClassic::parseHits($result->hits ?? null, $lang),
                facets: SearchResponseTransformerClassic::parseAggregations((object)$result->aggregations, $this->facet_config, $uri),
            );
        } catch (\Exception $e) {
            $this->log->error('Error on search with "' . $query . '": ' . $e);
        }
        return null;
    }

    private function getPageRanges(int $page, float|int $numOfPages): array
    {
        $array = [];
        $limit = 5;
        $startRange = $page;

        if ($startRange !== $numOfPages) {
            if ($startRange - 2 > 0) {
                $array[] = $startRange - 2;
            }
            if ($startRange - 1 > 0) {
                $array[] = $startRange - 1;
            }
            foreach (range($startRange, $numOfPages) as $i) {
                $array[] = $i;
                if (count($array) >= $limit) {
                    break;
                }
            }
            if (count($array) < $limit) {
                $missingValues = $limit - count($array);
                $firstArrayValue = $array[0] - 1;
                foreach (range($firstArrayValue, $firstArrayValue - $missingValues, -1) as $i) {
                    if ($i > 0) {
                        array_unshift($array, $i);
                        if (count($array) >= $limit) {
                            break;
                        }
                    } else {
                        break;
                    }
                }
            }
        }
        return $array;
    }

    private function transformQuery($query, $page, array $selectedFacets): string
    {
        $result = ElasticsearchService::convertToQuery($query, $this->facet_config, $page, $this->hitsNum, $selectedFacets);
        $this->log->debug('Elasticsearch query: ' . $result);
        return $result;
    }

}
