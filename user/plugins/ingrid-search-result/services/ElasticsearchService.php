<?php

namespace Grav\Plugin;

use stdClass;

class ElasticsearchService
{

    static function convertToQuery(string $query, $facet_config, int $page, int $hitsNum, array $selectedFacets, array $excludeFromSearch): string
    {
        if(count($excludeFromSearch) > 0) {
            foreach ($excludeFromSearch as $exclude) {
                $query .= ' -' . $exclude;
            }
        }
        $aggs = ElasticsearchService::mapFacets($query, $facet_config, $selectedFacets);
        $queryFromFacets = ElasticsearchService::getQueryFromFacets($facet_config, $selectedFacets);

        if ($query == "" && $queryFromFacets->query == "") {
            $result = array("match_all" => new stdClass());
        } else {
            SearchQueryHelper::replaceInGridQuery($query);
            $result = array("query_string" => array("query" => $query . " " . $queryFromFacets->query));
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

                list($queryString, $filter) = self::getFilterForFacet($selected_facets, $facets, $facet['id'], $query);
                foreach ($facet['queries'] as $subfacet_id => $subfacet_value) {
                    $result[$subfacet_id]['global'] = new stdClass();

                    $result = self::addFilterToFacet($filter, $queryString, $result, $subfacet_id);

                    $result[$subfacet_id]['aggs']['filtered']['aggs']['final'] = $subfacet_value['query'];
                }
            } else if (property_exists((object)$facet, 'query')) {
                // the facet should not depend on the actual query
                $result[$facet['id']]['global'] = new stdClass();

                // add filter for the facet
                list($queryString, $filter) = self::getFilterForFacet($selected_facets, $facets, $facet['id'], $query);
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

    /**
     * @param array $selected_facets
     * @param array $facets
     * @param $id
     * @param string $query
     * @return array
     */
    public static function getFilterForFacet(array $selected_facets, array $facets, $id, string $query): array
    {
        $queryString = array();
        $filter = array();
        if (empty($selected_facets)) {
            if ($query != "") {
                $queryString = array("query_string" => array("query" => $query));
            }
        } else {
            $queryFromFacets = ElasticsearchService::getQueryFromFacets($facets, $selected_facets, $id);
            if ($query == "" && $queryFromFacets->query == "") {
                $queryString = array("match_all" => new stdClass());
            } else {
                $finalQuery = $query ? "*" . $query . "* " : "";
                $queryString = array("query_string" => array("query" => $finalQuery . $queryFromFacets->query));
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
