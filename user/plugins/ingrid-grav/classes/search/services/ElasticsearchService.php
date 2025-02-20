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
        SearchQueryHelper::replaceInGridQuery($query);
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
    private static function mapFacets(string $query, array $facets, array $selected_facets, array $queryFields, string $queryStringOperator): object
    {
        $result = array();
        foreach ($facets as $facet) {
            if (property_exists((object)$facet, 'queries')) {

                list($queryString, $filter) = self::getFilterForFacet($selected_facets, $facets, $facet['id'], $query, $queryFields, $queryStringOperator);
                $queries = $facet['queries'];

                foreach ($queries as $subfacet_id => $subfacet_value) {
                    $result[$subfacet_id]['global'] = new stdClass();

                    $result = self::addFilterToFacet($filter, $queryString, $result, $subfacet_id);
                    $tmpQuery = $subfacet_value['query'];
                    $isToggle = (isset($facet['toggle']['active']) && $facet['toggle']['active']) ||
                        (isset($subfacet_value['toggle']['active']) && $subfacet_value['toggle']['active']);
                    if ($isToggle) {
                        if (isset($subfacet_value['query_toggle'])) {
                            $tmpQuery = $subfacet_value['query_toggle'];
                        }
                    }
                    $result[$subfacet_id]['aggs']['filtered']['aggs']['final'] = $tmpQuery;
                }
            } else if (property_exists((object)$facet, 'query')) {
                // the facet should not depend on the actual query
                $result[$facet['id']]['global'] = new stdClass();

                // add filter for the facet
                list($queryString, $filter) = self::getFilterForFacet($selected_facets, $facets, $facet['id'], $query, $queryFields, $queryStringOperator);
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
        } else if (property_exists((object)$foundObject, 'queries')) {
            $values = explode(",", $selectionValue);
            $queries = $foundObject['queries'];
            $temp_filter = array();
            $isToggle = isset($foundObject['toggle']['active']) && $foundObject['toggle']['active'];
            if ($isToggle) {
                foreach ($values as $value) {
                    if (isset($queries[$value])) {
                        $query = $queries[$value];
                        if (isset($query['query_toggle']['filter'])) {
                            $temp_filter[] = json_encode($query['query_toggle']['filter']);
                        } else {
                            $temp_filter[] = json_encode($query['query']['filter']);
                        }
                    } else {
                        foreach ($queries as $query) {
                            if (isset($query['query_toggle']['filter'])) {
                                $temp_filter[] = json_encode($query['query_toggle']['filter']);
                            } else if (isset($query['query']['filter'])) {
                                $temp_filter[] = json_encode($query['query']['filter']);
                            }
                        }
                    }
                }
            } else {
                foreach ($values as $value) {
                    if (isset($queries[$value])) {
                        $facet1 = $queries[$value];
                        if (property_exists((object)$facet1, 'query') && property_exists((object)$facet1['query'], 'filter')) {
                            $temp_filter[] = json_encode($facet1['query']['filter']);
                        } else {
                            $result[] = $queries[$selectionValue]['query'];
                        }
                    }
                }
            }
            // we need to combine facets within a group by OR
            $filter[] = '{"bool": { "should": [ ' . join(",", $temp_filter) . ']}}';
        } else if (property_exists((object)$foundObject, 'filter')) {
            $filter[] = sprintf($foundObject['filter'], ...explode(",", $selectionValue));
        }
        return array($result, $filter);
    }

    /**
     * @param $facet_config
     * @param int|string $selectionKey
     * @return array
     */
    public static function findByFacetId($facet_config, int|string $selectionKey): array
    {
        return array_filter($facet_config, function ($facet) use ($selectionKey) {
            $found = $facet['id'] === $selectionKey;
            if (!$found and isset($facet['toggle'])) {
                $toggle = $facet['toggle'];
                $found = $toggle['id'] === $selectionKey;
            }
            return $found;
        });
    }

    /**
     * @param array $selected_facets
     * @param array $facets
     * @param $id
     * @param string $query
     * @return array
     */
    public static function getFilterForFacet(array $selected_facets, array $facets, $id, string $query, array $queryFields, string $queryStringOperator): array
    {
        $queryString = array();
        $filter = array();
        $hasSelection = !empty($selected_facets);
        if (!$hasSelection) {
            if ($query != "") {
                $queryString = array("query_string" => array(
                    "query" => $query,
                    "fields" => $queryFields,
                    "default_operator" => $queryStringOperator,
                ));
            }
        } else {
            $finalSelectedFacets = $selected_facets;
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
     * @param int|string $subfacet_id
     * @return array
     */
    public static function addFilterToFacet(mixed $filter, mixed $queryString, array $result, int|string $subfacet_id): array
    {
        if ($filter || $queryString) {
            $result[$subfacet_id]['aggs']['filtered']['filter']['bool']['must'] = array();
            if ($filter) $result[$subfacet_id]['aggs']['filtered']['filter']['bool']['must'][] = $filter;
            if ($queryString) $result[$subfacet_id]['aggs']['filtered']['filter']['bool']['must'][] = $queryString;
        } else {
            $result[$subfacet_id]['aggs']['filtered']['filter']['match_all'] = new stdClass();
        }
        return $result;
    }

}
