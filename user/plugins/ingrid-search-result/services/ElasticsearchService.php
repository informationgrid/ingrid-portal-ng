<?php

namespace Grav\Plugin;

use stdClass;

class ElasticsearchService
{

    static function convertToQuery(string $query, $facet_config, int $page, int $hitsNum, array $selectedFacets): string
    {
        $aggs = ElasticsearchService::mapFacets($query, $facet_config, $selectedFacets);
        $queryFromFacets = ElasticsearchService::getQueryFromFacets($facet_config, $selectedFacets);

        if ($query == "" && $queryFromFacets->query == "") {
            $result = array("match_all" => new stdClass());
        } else {
            $result = array("query_string" => array("query" => $query . $queryFromFacets->query));
        }
        $filter = json_decode($queryFromFacets->filter);
        return json_encode(array(
            "from" => $page * $hitsNum,
            "size" => $hitsNum,
            "query" => array(
                "bool" => array(
                    "must" => $result,
                    "filter" => $filter
                )
            ),
            "aggs" => $aggs
        ));
    }

    private static function getQueryFromFacets($facet_config, array $selectedFacets, $ignoreFacetId = null): object
    {
        $result = array();
        $filter = array();
        foreach ($selectedFacets as $selectionKey => $selectionValue) {
            if ($selectionValue == "" || $selectionKey == $ignoreFacetId) continue;

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
    private static function mapFacets(string $query, array $facets, array $selected_facets): object
    {
        $result = array();
        foreach ($facets as $facet) {
            if (property_exists((object)$facet, 'queries')) {

                $queryString = array();
                $filter = array();
                if (!empty($selected_facets)) {

                    $queryFromFacets = ElasticsearchService::getQueryFromFacets($facets, $selected_facets, $facet['id']);
                    if ($query == "" && $queryFromFacets->query == "") {
                        $queryString = array("match_all" => new stdClass());
                    } else {
                        $queryString = array("query_string" => array("query" => $query . $queryFromFacets->query));
                    }
                    $filter = json_decode($queryFromFacets->filter);
                }
                foreach ($facet['queries'] as $subfacet_id => $subfacet_value) {
                    $result[$subfacet_id]['global'] = new stdClass();

                    if ($filter || $queryString) {
                        $result[$subfacet_id]['aggs']['filtered']['filter']['bool']['must'] = array();
                        $result[$subfacet_id]['aggs']['filtered']['filter']['bool']['must'][] = $filter;
                        $result[$subfacet_id]['aggs']['filtered']['filter']['bool']['must'][] = $queryString;
                    } else {
                        $result[$subfacet_id]['aggs']['filtered']['filter']['match_all'] = new stdClass();
                    }

                    $result[$subfacet_id]['aggs']['filtered']['aggs']['final'] = $subfacet_value['query'];
                }
            } else {
                if (property_exists((object)$facet, 'query')) {
                    // the facet should not depend on the actual query
                    $result[$facet['id']]['global'] = new stdClass();

                    // add filter for the facet
                    $queryString = array();
                    $filter = array();
                    if (!empty($selected_facets)) {
                        $queryFromFacets = ElasticsearchService::getQueryFromFacets($facets, $selected_facets, $facet['id']);
                        if ($query == "" && $queryFromFacets->query == "") {
                            $queryString = array("match_all" => new stdClass());
                        } else {
                            $queryString = array("query_string" => array("query" => $query . $queryFromFacets->query));
                        }
                        $filter = json_decode($queryFromFacets->filter);
                    }

                    if ($filter || $queryString) {
                        $result[$facet['id']]['aggs']['filtered']['filter']['bool']['must'] = array();
                        $result[$facet['id']]['aggs']['filtered']['filter']['bool']['must'][] = $filter;
                        $result[$facet['id']]['aggs']['filtered']['filter']['bool']['must'][] = $queryString;
                    } else {
                        $result[$facet['id']]['aggs']['filtered']['filter']['match_all'] = new stdClass();
                    }

                    // add actual facet
                    $result[$facet['id']]['aggs']['filtered']['aggs']['final'] = $facet['query'];
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
            $subQuery = array();
            foreach ($explodedSelection as $item) {
                $subQuery[] = sprintf($foundObject['search'], $item);
            }
            $result[] = join(' OR ', $subQuery);
        } else if (property_exists((object)$foundObject, 'queries')) {
            $values = explode(",", $selectionValue);
            $temp_filter = array();
            foreach ($values as $value) {
                $facet1 = $foundObject['queries'][$value];
                if (property_exists((object)$facet1, 'query') && property_exists((object)$facet1['query'], 'filter')) {
                    $temp_filter[] = json_encode($facet1['query']['filter']);
                } else {
                    $result[] = $foundObject['queries'][$selectionValue]['query'];
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
        return array_filter($facet_config, function ($object) use ($selectionKey) {
            return $object['id'] === $selectionKey;
        });
    }
}
