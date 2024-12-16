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
    private array $exclude;


    function __construct($grav, int $hitsNum, array $facetConfig = [], array $excludeFromSearch = [])
    {
        $this->facet_config = $facetConfig;
        $this->exclude = $excludeFromSearch;
        $this->api = $grav['config']->get('plugins.ingrid-detail.ingrid_api_url');
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
                'body' => $this->transformQuery($query, $page - 1, $selectedFacets, $this->exclude)
            ]);
            $result = json_decode($apiResponse->getBody()->getContents());
            $totalHits = $result->totalHits ?? 0;
            $numOfPages = $this->hitsNum == 0 ?
                0 : ceil($result->totalHits / $this->hitsNum) ?? 0;
            return new SearchResult(
                numOfHits: intval($totalHits),
                numOfPages: intval($numOfPages),
                numPage: $page,
                listOfPages: $this->getPageRanges($page, $numOfPages),
                hits: SearchResponseTransformerClassic::parseHits($result->hits ?? null, $lang),
                facets: SearchResponseTransformerClassic::parseAggregations((object)$result->aggregations, $this->facet_config, $uri, $lang),
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

    private function transformQuery($query, $page, array $selectedFacets, array $excludeFromSearch): string
    {
        $result = ElasticsearchService::convertToQuery($query, $this->facet_config, $page, $this->hitsNum, $selectedFacets, $excludeFromSearch);
        $this->log->debug('Elasticsearch query: ' . $result);
        return $result;
    }

}
