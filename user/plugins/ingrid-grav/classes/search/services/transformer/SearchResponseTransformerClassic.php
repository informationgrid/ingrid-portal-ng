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
            if (property_exists((object)$facetConfig, 'facets')) {
                foreach ($facetConfig['facets'] as $key => $query) {
                    if (isset($key)) {
                        $label = $query['label'] ?? strtoupper('FACETS.' . $facetConfig['id'] . '.' . $key);
                        if (isset($facetConfig['codelist']) or isset($query['codelist'])) {
                            $label = CodelistHelper::getCodelistEntryByIdent([$query['codelist'] ?? $facetConfig['codelist']], $key, $lang);
                        }
                        if (isset($query['query'])) {
                            $items[] = new FacetItem(
                                $key,
                                $label,
                                ((array)$aggregations)[$key]->doc_count,
                                SearchResponseTransformerClassic::createActionUrl($uri, $facetConfig["id"], $key, $config),
                                $query['icon'] ?? null,
                                $query['display_on_empty'] ?? false,
                            );
                        } else if (isset($query['facets'])) {
                            $splitFacets = $query['facets'];
                            $multiFacets = [];
                            foreach ($splitFacets as $splitFacetId => $splitFacetValue) {
                                $newKey = $key . '_' . $splitFacetId;
                                $item = [];
                                $item['label'] = $splitFacetValue['label'];
                                $item['count'] = ((array)$aggregations)[$newKey]->doc_count;
                                $item['actionLink'] = SearchResponseTransformerClassic::createActionUrl($uri, $facetConfig["id"], $key, $config);
                                if (isset($splitFacetValue['extend_href'])) {
                                    $item['actionLink'] = $item['actionLink'] . $splitFacetValue['extend_href'];
                                }
                                $multiFacets[] = $item;
                            }
                            $items[] = new FacetItemMulti(
                                $key,
                                $label,
                                $multiFacets,
                                $query['icon'] ?? null,
                                $query['display_on_empty'] ?? false,
                            );
                        }
                    }
                }
            } else if (property_exists((object)$facetConfig, 'query')) {
                $buckets = ((array)$aggregations)[$facetConfig['id']]->buckets;
                foreach ($buckets as $bucket) {
                    $key = $bucket->key;
                    if (isset($key)) {
                        $label = strtoupper('FACETS.' . $facetConfig['id'] . '.' . $key);
                        if (isset($facetConfig['codelist'])) {
                            $codelistValue = CodelistHelper::getCodelistEntryByIdent([$facetConfig['codelist']], $key, $lang);
                            if ($codelistValue) {
                                $label = $codelistValue;
                            }
                        }
                        $items[] = new FacetItem(
                            $key,
                            $label,
                            $bucket->final->doc_count ?? $bucket->doc_count,
                            SearchResponseTransformerClassic::createActionUrl($uri, $facetConfig["id"], $bucket->key, $config)
                        );
                    }
                }
            } else if ($facetConfig['id'] == 'bbox') {
                $items[] = new FacetItem(
                    '',
                    '',
                    -1,
                    SearchResponseTransformerClassic::createActionUrl($uri, 'bbox', null, $config)
                );
            }
            $label = $facetConfig['label'] ?? 'FACETS.FACET_LABEL.' . strtoupper($facetConfig['id']);
            $listLimit = $facetConfig['list_limit'] ?? null;
            $info = $facetConfig['info'] ?? null;
            $toggle = $facetConfig['toggle'] ?? null;
            $open = $facetConfig['open'] ?? false;
            $openBy = $facetConfig['open_by'] ?? null;
            $sort = $facetConfig['sort'] ?? null;
            switch ($sort) {
                case 'name':
                    usort($items, function ($a, $b) {
                        return strcasecmp($a->label, $b->label);
                    });
                    break;
                case 'count':
                    sort($items, function ($a, $b) {
                        return strcasecmp($a->docCount, $b->docCount);
                    });
                    break;
                default:
                    break;
            }
            $displayDependOn = $facetConfig['display_depend_on'] ?? null;
            $result[] = new FacetResult($facetConfig['id'], $label, $items, $listLimit, $info, $toggle, $open, $openBy, $displayDependOn);
        }

        return $result;
    }

    private static function createActionUrl($uri, $facetConfigId, $key, array $facetConfig): string {
        $query_params = $uri->query(null, true);

        // Get the full current URL without query parameters
        $base_url = $uri->path();
//        $search_term = $uri->post("q") ? "" : '&q=' . $uri->query("q");

        $query_string = array();
        if (isset($query_params[$facetConfigId])) {

            if ($facetConfigId == 'bbox') {
                unset($query_params[$facetConfigId]);
            } else {
                $valueAsArray = [];
                if (!empty($query_params[$facetConfigId])) {
                    $valueAsArray = explode(",", $query_params[$facetConfigId]);
                }
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
            foreach ($facetConfig as $facet) {
                if ($facet['id'] === $facetConfigId) {
                    if (isset($facet['select_other'])) {
                        foreach ($facet['select_other'] as $otherKey => $otherParam) {
                            if (!isset($query_params[$otherKey])) {
                                $query_params[$otherKey] = $otherParam;
                            } else {
                                $paramValues = [];
                                if (!empty($query_params[$otherKey])) {
                                    $paramValues = explode(',', $query_params[$otherKey]);
                                }
                                if (!in_array($otherParam, $paramValues)) {
                                    $paramValues[] = $otherParam;
                                    $query_params[$otherKey] = implode(',', $paramValues);
                                }
                            }
                        }
                    } else if (isset($facet['facets'])) {
                        foreach ($facet['facets'] as $subFacetKey => $subFacet) {
                            if ($key === $subFacetKey) {
                                if (isset($subFacet['active']) && $subFacet['active']) {
                                    $query_params[$facetConfigId] = '';
                                }
                                break;
                            }
                        }
                    }
                    break;
                }
            }
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
        switch ($theme) {
            case 'uvp':
            case 'uvp-ni':
                return SearchParserClassicUVP::parseHits($esHit, $lang);
            default:
                return SearchParserClassicISO::parseHits($esHit, $lang);
        }
    }

}
