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
        $aggs = ElasticsearchService::mapFacets($query, $facet_config, $selectedFacets, $queryFields, $queryStringOperator);
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
            "aggs" => $aggs,
            "sort" => $sortQuery ?? [],
        ));
    }

    private static function getQueryFromFacets($facet_config, array $selectedFacets, $ignoreFacetId = null): object
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
    private static function mapFacets(string $query, array $facetConfig, array $selectedFacets, array $queryFields, string $queryStringOperator): object
    {
        $result = array();
        foreach ($facetConfig as $facet) {
            if (property_exists((object)$facet, 'facets')) {

                list($queryString, $filter) = self::getFilterForFacet($selectedFacets, $facetConfig, $facet['id'], $query, $queryFields, $queryStringOperator);
                $facets = $facet['facets'];

                foreach ($facets as $subFacetId => $subFacetValue) {
                    $result[$subFacetId]['global'] = new stdClass();

                    $result = self::addFilterToFacet($filter, $queryString, $result, $subFacetId);
                    $tmpQuery = $subFacetValue['query'];
                    $isToggle = (isset($facet['toggle']['active']) && $facet['toggle']['active']) ||
                        (isset($subFacetValue['toggle']['active']) && $subFacetValue['toggle']['active']);
                    if ($isToggle) {
                        if (isset($subFacetValue['query_toggle'])) {
                            $tmpQuery = $subFacetValue['query_toggle'];
                        }
                    }
                    $result[$subFacetId]['aggs']['filtered']['aggs']['final'] = $tmpQuery;
                }
            } else if (property_exists((object)$facet, 'query')) {
                // the facet should not depend on the actual query
                $result[$facet['id']]['global'] = new stdClass();

                // add filter for the facet
                list($queryString, $filter) = self::getFilterForFacet($selectedFacets, $facetConfig, $facet['id'], $query, $queryFields, $queryStringOperator);
                $result = self::addFilterToFacet($filter, $queryString, $result, $facet['id']);

                // add actual facet
                $result[$facet['id']]['aggs']['filtered']['aggs']['final'] = $facet['query'];
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
            $subQuery = array();
            foreach ($explodedSelection as $item) {
                $subQuery[] = sprintf($foundObject['search'], $item);
            }
            $result[] = join(' OR ', $subQuery);
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

    /**
     * @param array $selectedFacets
     * @param array $facets
     * @param $id
     * @param string $query
     * @return array
     */
    public static function getFilterForFacet(array $selectedFacets, array $facets, $id, string $query, array $queryFields, string $queryStringOperator): array
    {
        $queryString = array();
        $filter = array();
        $hasSelection = !empty($selectedFacets);
        if (!$hasSelection) {
            if ($query != "") {
                $queryString = array("query_string" => array(
                    "query" => $query,
                    "fields" => $queryFields,
                    "default_operator" => $queryStringOperator,
                ));
            }
        } else {
            $finalSelectedFacets = $selectedFacets;
            $queryFromFacets = ElasticsearchService::getQueryFromFacets($facets, $finalSelectedFacets, $id);
            if ($query == "" && $queryFromFacets->query == "") {
                $queryString = array("match_all" => new stdClass());
            } else {
                $finalQuery = $query ? "*" . $query . "* " : "";
                $queryString = array("query_string" => array(
                    "query" => $finalQuery . $queryFromFacets->query,
                    "fields" => $queryFields,
                    "default_operator" => $queryStringOperator,
                ));
            }
            $filter = json_decode($queryFromFacets->filter);
        }
        return array($queryString, $filter);
    }

    /**
     * @param mixed $filter
     * @param mixed $queryString
     * @param array $result
     * @param int|string $subFacetId
     * @return array
     */
    public static function addFilterToFacet(mixed $filter, mixed $queryString, array $result, int|string $subFacetId): array
    {
        if ($filter || $queryString) {
            $result[$subFacetId]['aggs']['filtered']['filter']['bool']['must'] = array();
            if ($filter) $result[$subFacetId]['aggs']['filtered']['filter']['bool']['must'][] = $filter;
            if ($queryString) $result[$subFacetId]['aggs']['filtered']['filter']['bool']['must'][] = $queryString;
        } else {
            $result[$subFacetId]['aggs']['filtered']['filter']['match_all'] = new stdClass();
        }
        return $result;
    }

}
