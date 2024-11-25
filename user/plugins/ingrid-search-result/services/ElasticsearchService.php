<?php

namespace Grav\Plugin;

use stdClass;

class ElasticsearchService
{

    static function convertToQuery(string $query, $facet_config, int $page, int $hitsNum, array $selectedFacets): string
    {
        $aggs = ElasticsearchService::mapFacets($facet_config, $selectedFacets);
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

    private static function getQueryFromFacets($facet_config, array $selectedFacets): object
    {
        $result = array();
        $filter = array();
        foreach ($selectedFacets as $selectionKey => $selectionValue) {
            if ($selectionValue == "") continue;

            // Use array_filter to find the object with id 'test'
            $filteredObjects = self::findByFacetId($facet_config, $selectionKey);

            // Get the first matched object
            $foundObject = reset($filteredObjects);

            if ($foundObject) {
                list($result, $filter) = self::getQueryAndFilter($foundObject, $selectionValue, $result, $filter);
            }
        }

        $result_query = "";
        if (empty($result)) $result_query = "";
        else $result_query = '+(' . join(') +(', $result) . ')';

        return (object)array(
            "query" => $result_query,
            "filter" => '{"bool": { "must": [ '.join(",", $filter).']}}'
        );
    }

    /**
     * @param FacetConfig[] $facets
     * @return object
     */
    private static function mapFacets(array $facets, array $selected_facets): object
    {
        $result = array();
        foreach ($facets as $facet) {
            echo "Map facet: ".$facet['id'];
            if (property_exists((object)$facet, 'queries')) {

                var_dump($selected_facets);
                var_dump($facet['id']);
                if (!empty($selected_facets) && !property_exists((object)$selected_facets, $facet['id'])) {
                    echo 'add facet filter (queries)';
                    foreach ($selected_facets as $selectionKey => $selectionValue) {
                        $otherFacet = self::findByFacetId($facets, $selectionKey);
                        $firstOtherFacet = reset($otherFacet);
                        list($subResult, $subFilter) = self::getQueryAndFilter($firstOtherFacet, $selectionValue, array(), array());
                        echo json_encode($subResult);
                        echo json_encode($subFilter);
                    }
                }
                foreach ($facet['queries'] as $subfacet_id => $subfacet_value) {
//                    echo 'Queries';
//                    var_dump($subfacet_id);
                    $result[$subfacet_id]['global'] = new stdClass();
                    if ($subFilter) {
                        $result[$subfacet_id]['aggs']['filtered']['filter'] = reset($subFilter);
                    }
                    else if ($subResult) {
                        echo "HAS SUBFILTER:";
                        $finalFilter = $subResult;
                        $result[$subfacet_id]['aggs']['filtered']['filter']['query_string']['query'] = reset($subResult);
                    } else {
                        $result[$subfacet_id]['aggs']['filtered']['filter']['match_all'] = new stdClass();
                    }
                    $result[$subfacet_id]['aggs']['filtered']['aggs']['final'] = $subfacet_value['query'];
                }
            } else {
                if (property_exists((object)$facet, 'query')) {
                    // the facet should not depend on the actual query
                    $result[$facet['id']]['global'] = new stdClass();

//                    var_dump($selected_facets);
                    // add filter for the facet
                    if (!empty($selected_facets)) { // && !property_exists((object)$selected_facets, $facet['id'])) {
                        echo 'add facet filter (query): '.$facet['id'];
                        foreach ($selected_facets as $selectionKey => $selectionValue) {
                            echo "check: ".$selectionKey;
                            if ($selectionKey == $facet['id']) {continue;}
                            echo "handle: ".$selectionKey;

                            $otherFacet = self::findByFacetId($facets, $selectionKey);
                            $firstOtherFacet = reset($otherFacet);
                            list($subResult, $subFilter) = self::getQueryAndFilter($firstOtherFacet, $selectionValue, array(), array());

                            $finalFilter = reset($subFilter);
                            $result[$facet['id']]['aggs']['filtered']['filter'] = json_decode($finalFilter);
                        }
                    }

                    if (!property_exists((object)$result[$facet['id']], 'aggs')) {
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
            $result[] = sprintf($foundObject['search'], $selectionValue);
        } else if (property_exists((object)$foundObject, 'queries')) {
//            echo 'FOUND QUERIES:'.$selectionValue;
            $values = explode(",", $selectionValue);
            foreach ($values as $value) {
                $facet1 = $foundObject['queries'][$value];
//                echo 'internal found';
//                var_dump($facet1);
                if (property_exists((object)$facet1, 'query') && property_exists((object)$facet1['query'], 'filter')) {
                    $filter[] = json_encode($facet1['query']['filter']);
                } else {
                    $result[] = $foundObject['queries'][$selectionValue]['query'];
                }
            }
        } else if (property_exists((object)$foundObject, 'filter')) {
            $filter[] = sprintf($foundObject['filter'], ...explode(",", $selectionValue));
        }
//        echo "QUERY:".json_encode($filter);
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
