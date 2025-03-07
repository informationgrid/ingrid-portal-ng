<?php

namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Uri;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SearchServiceImpl implements SearchService
{

    private string $api;
    private int $hitsNum;
    private Client $client;
    private $log;
    private array $facet_config;
    private array $addToSearch;
    private bool $sortByDate;
    private array $queryFields;
    private string $queryStringOperator;
    private array $requestedFields;
    private array $searchSourceSettings;

    function __construct(Grav $grav, Uri $uri, array $facetConfig, array $searchSettings)
    {
        $this->api = $grav['config']->get('plugins.ingrid-grav.ingrid_api.url');
        $this->client = new Client(['base_uri' => $this->api]);
        $this->log = $grav['log'];
        $this->facet_config = $facetConfig;
        $this->addToSearch = $searchSettings['add_to_search'] ?? [];
        $this->hitsNum = $searchSettings['hits_num'] ?? 0;
        $this->queryFields = $searchSettings['query_fields'] ?? [];
        $this->queryStringOperator = $searchSettings['query_string_operator'] ?? 'AND';
        $this->requestedFields = $searchSettings['requested_fields'] ?? [];
        $this->searchSourceSettings = $searchSettings['source'] ?? [];
        $sortByDate = $searchSettings['sort']['sortByDate'] ?? false;
        $ranking = $uri->query('ranking') ?? '';
        if (!empty($ranking)) {
            if ($ranking === 'date') {
                $sortByDate = true;
            } else {
                $sortByDate = false;
            }
        }
        $this->sortByDate = $sortByDate;
    }


    /**
     * @param string $query
     * @param int $page
     * @param array $selectedFacets
     * @param $uri
     * @param string $lang
     * @param string $theme
     * @return SearchResult|null
     * @throws GuzzleException
     */
    public function getSearchResults(string $query, int $page, array $selectedFacets, $uri, string $lang, string $theme = ''): ?SearchResult
    {
        try {
            $apiResponse = $this->client->request('POST', 'portal/search', [
                'body' => $this->transformQuery($query, $page - 1, $selectedFacets)
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
                hits: SearchResponseTransformerClassic::parseHits($result->hits ?? null, $lang, $theme),
                facets: isset($result->aggregations->global_filter_aggregations->global_filter) ? SearchResponseTransformerClassic::parseAggregations((object)$result->aggregations->global_filter_aggregations->global_filter, $this->facet_config, $uri, $lang) : null,
            );
        } catch (\Exception $e) {
            $this->log->error('Error on search with "' . $query . '": ' . $e);
        }
        return null;
    }

    public function getSearchResultsUnparsed(string $query, int $page, array $selectedFacets): ?array
    {
        try {
            $apiResponse = $this->client->request('POST', 'portal/search', [
                'body' => $this->transformQuery($query, $page - 1, $selectedFacets)
            ]);
            $result = json_decode($apiResponse->getBody()->getContents());
            return $result->hits;
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
        SearchQueryHelper::replaceInGridQuery($query);
        SearchQueryHelper::transformColonQuery($query);
        $result = ElasticsearchService::convertToQuery($query, $this->facet_config, $page, $this->hitsNum, $selectedFacets, $this->addToSearch, $this->sortByDate, $this->queryFields, $this->queryStringOperator, $this->requestedFields, $this->searchSourceSettings);
        $this->log->debug('Elasticsearch query: ' . $result);
        return $result;
    }

}
