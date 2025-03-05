<?php

namespace Grav\Plugin;

use stdClass;

class ElasticsearchService
{

    static function convertToQuery(string $query, $facet_config, int $page, int $hitsNum, array $selectedFacets, array $addToSearch, bool $sortByDate, array $queryFields, string $queryStringOperator): string
    {
        if (count($addToSearch) > 0) {
            $query .= ' ' . implode(' ', $addToSearch);
        }
        $aggs = ElasticsearchService::mapFacets($facet_config, $selectedFacets);
        $queryFromFacets = ElasticsearchService::getQueryFromFacets($facet_config, $selectedFacets);

        if ($query == "" && $queryFromFacets->query == "") {
            $result = array("match_all" => new stdClass());
        } else {
            $result = array("query_string" => array(
                "query" => $query . " " . $queryFromFacets->query,
                "fields" => $queryFields,
                "default_operator" => $queryStringOperator,
            ));
        }

        $sortQuery = array(
            "_score"
        );
        if ($sortByDate) {
            $sortQuery[] = array(
                "t01_object.mod_time" => array(
                    "order" => "desc"
                )
            );
        }
        $filter = json_decode($queryFromFacets->filter);
        return json_encode(array(
            "from" => $page * $hitsNum,
            "size" => $hitsNum,
            "track_total_hits" => true,
            "query" => array(
                "bool" => array(
                    "must" => $result,
                    "filter" => $filter
                )
            ),
            "aggs" => array(
                "global_filter_aggregations" => array(
                    "global" => new stdClass(),
                    "aggs" => array(
                        "global_filter" => array(
                            "filter" => array(
                                "bool" => array(
                                    "must" => array(
                                        "query_string" => array(
                                            "query" => $query,
                                            "fields" => $queryFields,
                                            "default_operator" => $queryStringOperator
                                        )
                                    )
                                )
                            ),
                            "aggs" => $aggs
                        )
                    )
                )
            ),
            "sort" => $sortQuery ?? [],
        ));
    }

    private static function getQueryFromFacets(array $facet_config, array $selectedFacets, ?string $ignoreFacetId = null): object
    {
        $result = array();
        $filter = array();
        foreach ($selectedFacets as $selectionKey => $selectionValue) {
            if ($selectionValue == "" || $selectionKey == $ignoreFacetId) continue;

            if (isset($selectedFacets[$selectionValue])) continue;

            $filteredObjects = self::findByFacetId($facet_config, $selectionKey);

            // Get the first matched object
            $foundObject = reset($filteredObjects);

            if ($foundObject) {
                list($result, $filter) = self::getQueryAndFilter($foundObject, $selectionValue, $result, $filter);
            }
        }

        $result_query = empty($result) ? "" : '+(' . join(') +(', $result) . ')';

        $finalFilter = "";
        if (count($filter) === 0) {
            $finalFilter = '{"match_all": {}}';
        } else {
            $finalFilter = '{"bool": { "must": [ ' . join(",", $filter) . ']}}';
        }


        return (object)array(
            "query" => $result_query,
            "filter" => $finalFilter
        );
    }

    /**
     * @param FacetConfig[] $facets
     * @return object
     */
    private static function mapFacets(array $facetConfig, array $selectedFacets): object
    {
        $result = array();
        foreach ($facetConfig as $facet) {
            if (property_exists((object)$facet, 'facets')) {
                $facets = $facet['facets'];
                foreach ($facets as $subFacetId => $subFacetValue) {
                    if (isset($subFacetValue['query'])) {
                        $tmpQuery = $subFacetValue['query'];
                        $isToggle = (isset($facet['toggle']['active']) && $facet['toggle']['active']) ||
                            (isset($subFacetValue['toggle']['active']) && $subFacetValue['toggle']['active']);
                        if ($isToggle) {
                            if (isset($subFacetValue['query_toggle'])) {
                                $tmpQuery = $subFacetValue['query_toggle'];
                            }
                        }
                        $result[$subFacetId]['filter']['bool']['must'][] = $tmpQuery['filter'];
                        self::addFilterToFacet($result[$subFacetId]['filter']['bool']['must'], $facetConfig, $selectedFacets, $facet['id']);
                    } else if (isset($subFacetValue['facets'])) {
                        $splitFacets = $subFacetValue['facets'];
                        foreach ($splitFacets as $splitFacetId => $splitFacetValue) {
                            $splitId = $subFacetId . '_' . $splitFacetId;
                            $tmpQuery = $splitFacetValue['query'];
                            $result[$splitId]['filter']['bool']['must'][] = $tmpQuery['filter'];
                        }
                    }
                }
            } else if (property_exists((object)$facet, 'query')) {
                if (isset($facet['query']['terms'])) {
                    $result[$facet['id']] = $facet['query'];
                    $result[$facet['id']]['aggs']['final']['filter']['bool']['must'] = [];
                    self::addFilterToFacet($result[$facet['id']]['aggs']['final']['filter']['bool']['must'], $facetConfig, $selectedFacets, $facet['id']);
                }
            }
        }
        return (object)$result;
    }

    /**
     * @param mixed $foundObject
     * @param mixed $selectionValue
     * @param array $result
     * @param array $filter
     * @return array
     */
    public static function getQueryAndFilter(mixed $foundObject, mixed $selectionValue, array $result, array $filter): array
    {
        if (property_exists((object)$foundObject, 'search')) {
            $explodedSelection = explode(",", $selectionValue);
            $tempFilter = array();
            if (count($explodedSelection) > 1) {
                foreach ($explodedSelection as $item) {
                    $tempFilter[] = '{ "query_string": { "query":"' . sprintf($foundObject['search'], $item) . '"}}';
                }
                $filter[] = '{"bool": { "should": [ ' . join(",", $tempFilter) . ']}}';
            } else {
                $filter[] = '{ "query_string": { "query":"' . sprintf($foundObject['search'], $explodedSelection[0]) . '"}}';
            }
        } else if (property_exists((object)$foundObject, 'facets')) {
            $values = explode(",", $selectionValue);
            $facets = $foundObject['facets'];
            $tempFilter = array();
            $isToggle = isset($foundObject['toggle']['active']) && $foundObject['toggle']['active'];
            if ($isToggle) {
                foreach ($values as $value) {
                    if (isset($facets[$value])) {
                        $query = $facets[$value];
                        if (isset($query['query_toggle']['filter'])) {
                            $tempFilter[] = json_encode($query['query_toggle']['filter']);
                        } else {
                            $tempFilter[] = json_encode($query['query']['filter']);
                        }
                    } else {
                        foreach ($facets as $query) {
                            if (isset($query['query_toggle']['filter'])) {
                                $tempFilter[] = json_encode($query['query_toggle']['filter']);
                            } else if (isset($query['query']['filter'])) {
                                $tempFilter[] = json_encode($query['query']['filter']);
                            }
                        }
                    }
                }
            } else {
                foreach ($values as $value) {
                    if (isset($facets[$value])) {
                        $facet = $facets[$value];
                        if (property_exists((object)$facet, 'query') && property_exists((object)$facet['query'], 'filter')) {
                            $tempFilter[] = json_encode($facet['query']['filter']);
                        } else {
                            $result[] = $facets[$selectionValue]['query'];
                        }
                    }
                }
            }
            // we need to combine facets within a group by OR
            $filter[] = '{"bool": { "should": [ ' . join(",", $tempFilter) . ']}}';
        } else if (property_exists((object)$foundObject, 'filter')) {
            $filter[] = sprintf($foundObject['filter'], ...explode(",", $selectionValue));
        }
        return array($result, $filter);
    }

    /**
     * @param $facetConfig
     * @param int|string $selectionKey
     * @return array
     */
    public static function findByFacetId($facetConfig, int|string $selectionKey): array
    {
        return array_filter($facetConfig, function ($facet) use ($selectionKey) {
            $found = $facet['id'] === $selectionKey;
            if (!$found and isset($facet['toggle'])) {
                $toggle = $facet['toggle'];
                $found = $toggle['id'] === $selectionKey;
            }
            return $found;
        });
    }

    public static function addFilterToFacet(array &$filterMust, array $facetConfig, array $selectedFacets, string $facetId): void
    {
        foreach ($selectedFacets as $selectedFacetId => $selectedFacetValues) {
            if ($selectedFacetId !== $facetId) {
                if ($selectedFacetId === 'bbox') {
                    $selectedFacet = self::findByFacetId($facetConfig, $selectedFacetId);

                    // Get the first matched object
                    $foundObject = reset($selectedFacet);

                    if (isset($foundObject['filter'])) {
                        $filter = sprintf($foundObject['filter'], ...explode(",", $selectedFacetValues));
                        if (str_starts_with($filter, '{')) {
                            $splits = explode(",", $filter);
                            foreach ($splits as $split) {
                                $filterMust[] = json_decode($split);
                            }
                        }
                    }
                } else {
                    $values = explode(",", $selectedFacetValues);
                    foreach ($values as $value) {
                        $selectedFacet = self::findByFacetId($facetConfig, $selectedFacetId);

                        // Get the first matched object
                        $foundObject = reset($selectedFacet);

                        if ($foundObject) {
                            if (isset($foundObject['search'])) {
                                $filterMust[] = array(
                                    "query_string" => array(
                                        "query" => sprintf($foundObject['search'], $value)
                                    )
                                );
                            } else if (isset($foundObject['facets'])) {
                                if (isset($foundObject['toggle']) && $foundObject['toggle']['id'] === $selectedFacetId) {
                                    $toggleQueries = [];
                                    if (!empty($value)) {
                                        foreach ($foundObject['facets'] as $toggleFacet) {
                                            $toggleQueries[] = $toggleFacet['query_toggle']['filter'] ?? $toggleFacet['query']['filter'];
                                        }
                                    }
                                    $filterMust[] = array(
                                        "bool" => array(
                                            "should" => $toggleQueries,
                                        )
                                    );
                                } else {
                                    $filterMust[] = $foundObject['facets'][$value]['query']['filter'];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

}
