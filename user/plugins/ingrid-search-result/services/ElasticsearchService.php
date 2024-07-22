<?php

namespace Grav\Plugin;

use stdClass;

class ElasticsearchService
{

    static function convertToQuery(string $query, $facet_config, int $page, int $hitsNum, array $selectedFacets): string
    {
        $aggs = ElasticsearchService::mapFacets($facet_config);
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
            // Use array_filter to find the object with id 'test'
            $filteredObjects = array_filter($facet_config, function ($object) use ($selectionKey) {
                return $object['id'] === $selectionKey;
            });

            // Get the first matched object
            $foundObject = reset($filteredObjects);

            if ($foundObject) {
                if (property_exists((object)$foundObject, 'search')) {
                    $result[] = sprintf($foundObject['search'], $selectionValue[0]);
                } else if (property_exists((object)$foundObject, 'queries')) {
                    $result[] = $foundObject['queries'][$selectionValue[0]]['search'];
                } else if (property_exists((object)$foundObject, 'filter')) {
                    $filter[] = sprintf($foundObject['filter'], ...explode(",", $selectionValue));
                }
            }
        }

        $result_query = "";
        if (empty($result)) $result_query = "";
        else $result_query = '+(' . join(') +(', $result) . ')';

        return (object)array(
            "query" => $result_query,
            "filter" => join(",", $filter)
        );
    }

    /**
     * @param FacetConfig[] $facets
     * @return object
     */
    private static function mapFacets(array $facets): object
    {
        $result = array();
        foreach ($facets as $facet) {
            if (property_exists((object)$facet, 'queries')) {
                foreach ($facet['queries'] as $subfacet_id => $subfacet_value) {
                    $result[$subfacet_id] = $subfacet_value['query'];
                }
            } else {
                if (property_exists((object)$facet, 'query')) {
                    $result[$facet['id']] = $facet['query'];
                }
            }
        }
        return (object)$result;
    }
}
