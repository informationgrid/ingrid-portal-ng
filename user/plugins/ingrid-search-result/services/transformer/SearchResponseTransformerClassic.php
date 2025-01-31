<?php

namespace Grav\Plugin;

use Grav\Common\Plugin;

class SearchResponseTransformerClassic
{
    public static function parseHits(array $hits, string $lang, string $theme): array
    {
        return array_map(
            function($hit) use ($lang, $theme) { return self::parseHit($hit, $lang, $theme); },
            $hits
        );
    }


    /**
     * @param object $aggregations
     * @param FacetConfig[] $config
     * @return FacetResult[]
     */
    public static function parseAggregations(object $aggregations, array $config, $uri, string $lang): array
    {
        $result = array();

        foreach ($config as $facetConfig) {
            $items = array();
            if (property_exists((object)$facetConfig, 'queries')) {
                foreach ($facetConfig['queries'] as $key => $query) {
                    if (isset($key)) {
                        $label = $query['label'] ?? strtoupper('FACETS.' . $facetConfig['id'] . '.' . $key);
                        if (isset($facetConfig['codelist']) or isset($query['codelist'])) {
                            $label = CodelistHelper::getCodelistEntryByIdent([$query['codelist'] ?? $facetConfig['codelist']], $key, $lang);
                        }
                        $items[] = new FacetItem(
                            $key,
                            $label,
                            ((array)$aggregations)[$key]->filtered->final->doc_count,
                            SearchResponseTransformerClassic::createActionUrl($uri, $facetConfig["id"], $key),
                            $query['icon'] ?? null,
                            $query['display'] ?? false,
                        );
                    }
                }
            } else if (property_exists((object)$facetConfig, 'query')) {
                $buckets = ((array)$aggregations)[$facetConfig['id']]->filtered->final->buckets;
                foreach ($buckets as $bucket) {
                    $key = $bucket->key;
                    if (isset($key)) {
                        $label = strtoupper('FACETS.' . $facetConfig['id'] . '.' . $key);
                        if (isset($facetConfig['codelist'])) {
                            $label = CodelistHelper::getCodelistEntryByIdent([$facetConfig['codelist']], $key, $lang);
                        }
                        $items[] = new FacetItem(
                            $key,
                            $label,
                            $bucket->doc_count,
                            SearchResponseTransformerClassic::createActionUrl($uri, $facetConfig["id"], $bucket->key),
                            null,
                            false,
                        );
                    }
                }
            } else if ($facetConfig['id'] == 'bbox') {
                $items[] = new FacetItem(
                    '',
                    '',
                    -1,
                    SearchResponseTransformerClassic::createActionUrl($uri, 'bbox', null),
                    null,
                    false,
                );
            }
            $label = $facetConfig['label'] ?? 'FACETS.FACET-LABEL.' . strtoupper($facetConfig['id']);
            $listLimit = $facetConfig['listLimit'] ?? null;
            $info = $facetConfig['info'] ?? null;
            $toggle = $facetConfig['toggle'] ?? null;
            $sort = $facetConfig['sort'] ?? null;
            if ($sort == 'name') {
                usort($items, function ($a, $b) {
                    return strcasecmp($a->label, $b->label);
                });
            } else if ($sort == 'count') {
                sort($items, function ($a, $b) {
                    return strcasecmp($a->docCount, $b->docCount);
                });
            }
            $result[] = new FacetResult($facetConfig['id'], $label, $items, $listLimit, $info, $toggle);
        }

        return $result;
    }

    private static function createActionUrl($uri, $facetConfigId, $key): string {
        $query_params = $uri->query(null, true);

        // Get the full current URL without query parameters
        $base_url = $uri->path();
//        $search_term = $uri->post("q") ? "" : '&q=' . $uri->query("q");

        $query_string = array();
        if (isset($query_params[$facetConfigId])) {

            if ($facetConfigId == 'bbox') {
                unset($query_params[$facetConfigId]);
            } else {
                $valueAsArray = explode(",", $query_params[$facetConfigId]);
                $found = array_search($key, $valueAsArray);
                if ($found !== false) {
                    array_splice($valueAsArray, $found, 1);
                } else {
                    $valueAsArray[] = $key;
                }
                if (count($valueAsArray) > 0) {
                    $query_params[$facetConfigId] = implode(",", $valueAsArray);
                } else {
                    unset($query_params[$facetConfigId]);
                }
            }
        } else {
            $query_params[$facetConfigId] = $key;
        }

        if (isset($query_params['more'])) {
            unset($query_params['more']);
        }
        if (isset($query_params['page'])) {
            unset($query_params['page']);
        }

        $query_string[] = http_build_query($query_params);

        // Construct the new URL with the updated query string
        return $base_url . '?' . join('&', $query_string);
    }

    private static function parseHit($esHit, string $lang, string $theme): array
    {
        $source = $esHit->_source;

        switch ($theme) {
            case 'uvp':
            case 'uvp-ni':
                return ClassicParserUVP::parseHits($source, $lang);
            default:
                return ClassicParserISO::parseHits($source, $lang);
        }
    }

}
