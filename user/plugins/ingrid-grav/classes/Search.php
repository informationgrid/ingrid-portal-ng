<?php

namespace Grav\Plugin;

use Grav\Common\Grav;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Exception;

class Search
{

    var Grav $grav;
    var string $configApi;
    var string $lang;
    var string $theme;
    var null|SearchResult $results;
    var string $query;
    var array $selectedFacets;
    var int $hitsNum;
    var string $ranking;
    var int $page;

    public function __construct(Grav $grav, string $api)
    {
        $this->grav = $grav;
        $this->configApi = $api;
        $this->lang = $grav['language']->getLanguage();
        $this->theme = $this->grav['config']->get('system.pages.theme');
        $this->results = null;
        $this->query = $this->grav['uri']->query('q') ?: '';
        $this->selectedFacets = [];
        $this->hitsNum = 0;
        $this->ranking = '';
    }


    public function getContent(): void
    {
        $this->page = $this->grav['uri']->query('page') ?: 1;
        $this->ranking = $this->grav['uri']->query('ranking') ?: '';

        // Theme config
        $facetConfig = $this->grav['config']->get('themes.' . $this->theme . '.hit_search.facet_config') ?: [];
        $this->hitsNum = $this->grav['config']->get('themes.' . $this->theme . '.hit_search.hits_num') ?: 0;
        $addToSearch = $this->grav['config']->get('themes.' . $this->theme . '.hit_search.add_to_search') ?: [];
        $sortByDate = $this->grav['config']->get('themes.' . $this->theme . '.hit_search.sort.sortByDate') ?: false;

        if (!empty($this->ranking)) {
            if ($this->ranking === 'date') {
                $sortByDate = true;
            } else {
                $sortByDate = false;
            }
        } else {
            if ($sortByDate) {
                $ranking = 'date';
            } else {
                $ranking = 'score';
            }
        }
        $this->addFacetsBySelection($facetConfig);
        $this->selectedFacets = $this->getSelectedFacets($facetConfig);
        $service = new SearchServiceImpl($this->grav, $this->hitsNum, $facetConfig, $addToSearch, $sortByDate);
        $this->results = $service->getSearchResults($this->query, $this->page, $this->selectedFacets, $this->grav['uri'], $this->lang, $this->theme);
    }

    public function getContentMapLegend(): void
    {
        $this->hitsNum = 0;
        // Theme config
        $facetConfig = $this->grav['config']->get('themes.' . $this->theme . '.map.leaflet.legend.facet_config') ?? [];
        if ($this->theme === 'uvp') {
            $service = new SearchServiceImpl($this->grav, $this->hitsNum, $facetConfig, []);
            $results = $service->getSearchResults("", 1, [], $this->grav['uri'], $this->lang);
            if ($results) {
                $this->results = $results;
            }
        }
    }

    public function getContentMapMarkers(): array
    {
        $output = [];

        if ($this->theme === 'uvp') {
            $this->page = $this->grav['uri']->query('page') ?: '';
            $this->hitsNum = $this->grav['config']->get('themes.' . $this->theme . '.map.leaflet.legend.marker_num') ?: 100;
            $facetConfig = $this->grav['config']->get('themes.' . $this->theme . '.map.leaflet.legend.facet_config') ?? [];
            $this->selectedFacets = $this->getSelectedFacets($facetConfig);

            $service = new SearchServiceImpl($this->grav, $this->hitsNum, $facetConfig, []);
            $hits = $service->getSearchResultOriginalHits('', $this->page, $this->selectedFacets);
            if ($hits) {
                $output = $this->getMapMarkers($hits);
            }
        }
        return $output;
    }

    private function addFacetsBySelection(array &$facetConfig): void
    {
        $queryParams = $this->grav['uri']->query(null, true);
        $extendedFacets = array_filter($facetConfig, function ($facet) {
            return isset($facet['extend_facet_selection_config']);
        });
        if (!empty($extendedFacets)) {
            foreach ($facetConfig as $facetKey => $facet) {
                if (isset($queryParams[$facet['id']])) {
                    $addFacet = $facet['extend_facet_selection_config'] ?? null;
                    if ($addFacet) {
                        $field = $addFacet['field'] ?? null;
                        if ($field === 'provider') {
                            $listLimit = $addFacet['list_limit'] ?? null;
                            $sort = $addFacet['sort'] ?? null;
                            $partners = CodelistHelper::getCodelistPartnerProviders();
                            $paramValues = array_reverse(explode(',', $queryParams[$facet['id']]));
                            foreach ($paramValues as $value) {
                                $items = array_filter($partners, function ($partner) use ($value) {
                                    return $partner['ident'] === $value;
                                });
                                foreach ($items as $item) {
                                    if ($item['ident'] == $value) {
                                        $providers = $item['providers'];
                                        $newFacets = [];
                                        foreach ($providers as $provider) {
                                            $newFacets[$provider['ident']] = array(
                                                "label" => $provider['name'],
                                                "query" => array(
                                                    "filter" => array(
                                                        "term" => array(
                                                            $field => $provider['ident']
                                                        )
                                                    )
                                                )
                                            );
                                        }
                                        if (!empty($newFacets)) {
                                            array_splice($facetConfig, $facetKey + 1, 0, array(
                                                array(
                                                    "id" => $value,
                                                    "label" => $item['name'],
                                                    "list_limit" => $listLimit,
                                                    "sort" => $sort,
                                                    "facets" => $newFacets
                                                )
                                            ));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    private function getSelectedFacets(array &$facetConfig): array
    {
        $queryParams = $this->grav['uri']->query(null, true);
        foreach ($queryParams as $key => $param) {
            $list = array_filter($facetConfig, function ($facet) use ($key) {
                $found = $facet['id'] === $key;
                if (!$found and isset($facet['toggle'])) {
                    $toggle = $facet['toggle'];
                    $found = $toggle['id'] === $key;
                }
                return $found;
            });
            if (empty($list)) {
                unset($queryParams[$key]);
            }
        }
        $this->getSelectedFacetsFromConfig($facetConfig, $queryParams, null);
        return $queryParams;
    }

    public function getPagingUrl(mixed $uri): string
    {
        $url = "";
        $query_params = $uri->query(null, true);

        if (isset($query_params['more'])) {
            unset($query_params['more']);
        }
        if (isset($query_params['page'])) {
            unset($query_params['page']);
        }
        if (isset($query_params['ranking'])) {
            unset($query_params['ranking']);
        }
        $query_string[] = http_build_query($query_params);

        $url .= '?' . join('&', $query_string);
        return $url;
    }

    private function getSelectedFacetsFromConfig(array &$facets, array &$params, null|string $parentId): void
    {
        $values = [];
        foreach ($facets as $key => $facet) {
            $id = $key;
            if (isset($facet['id'])) {
                $id = $facet['id'];
            }

            if (isset($facet['toggle'])) {
                $toggle = $facet['toggle'];
                $toggleId = $toggle['id'];
                $isToggleActive = false;
                if (isset($params[$toggleId])) {
                    $isToggleActive = !empty($params[$toggleId]);
                } else {
                    $isToggleActive = $toggle['active'] ?? false;
                }
                $facets[$key]['toggle']['active'] = $isToggleActive;
                if (isset($toggle['active']) and $isToggleActive) {
                    if (!isset($params[$toggle['id']])) {
                        $params[$toggle['id']] = $parentId ?? $id;
                    }
                }
            }
            if ($parentId) {
                if (isset($facet['active']) and $facet['active']) {
                    if (!isset($params[$id])) {
                        $values[] = $id;
                    }
                }
            }

            if (isset($facet['facets'])) {
                $this->getSelectedFacetsFromConfig($facet['facets'], $params, $id);
            }
        }
        if ($parentId and !empty($values)) {
            if (!isset($params[$parentId])) {
                $params[$parentId] = implode(',', $values);
            }
        }
    }

    private function getMapMarkers(array $hits): array
    {
        $items = [];
        foreach ($hits as $source) {
            $hit = $source->_source;
            $item = [];
            $item['title'] = $this->getValue($hit, 'blp_name') ?? $this->getValue($hit, 'title');
            $item['lat'] = $this->getValue($hit, 'lat_center');
            $item['lon'] = $this->getValue($hit, 'lon_center');
            $item['iplug'] = $this->getValue($hit, 'iPlugId');
            $item['uuid'] = $this->getValue($hit, 't01_object.obj_id');
            $y1 = $this->getValue($hit, 'y1');
            $x1 = $this->getValue($hit, 'x1');
            $y2 = $this->getValue($hit, 'y2');
            $x2 = $this->getValue($hit, 'x2');
            if (in_array('blp', $this->getValue($hit, 'datatype'))) {
                $item['isBLP'] = true;
                $bbox = [];
                $bbox[] = [$y1, $x1];
                $bbox[] = [$y2, $x2];
                $item['bbox'] = $bbox;
                $item['bpInfos'] = [];
                $blpUrlFinished = $this->getValue($hit, 'blp_url_finished');
                $blpUrlProgress = $this->getValue($hit, 'blp_url_in_progress');
                $fnpUrlFinished = $this->getValue($hit, 'fnp_url_finished');
                $fnpUrlProgress = $this->getValue($hit, 'fnp_url_in_progress');
                $bpUrlFinished = $this->getValue($hit, 'bp_url_finished');
                $bpUrlProgress = $this->getValue($hit, 'bp_url_in_progress');

                if (!empty($blpUrlProgress)) {
                    $itemInfo = [];
                    $itemInfo["url"] = $blpUrlProgress;
                    $itemInfo["tags"] = "p";
                    $item['bpInfos'][] = $itemInfo;
                }
                if (!empty($blpUrlFinished)) {
                    $itemInfo = [];
                    $itemInfo["url"] = $blpUrlFinished;
                    $itemInfo["tags"] = "v";
                    $item['bpInfos'][] = $itemInfo;
                }
                if (!empty($fnpUrlProgress)) {
                    $itemInfo = [];
                    $itemInfo["url"] = $fnpUrlProgress;
                    $itemInfo["tags"] = "p";
                    $item['bpInfos'][] = $itemInfo;
                }
                if (!empty($fnpUrlFinished)) {
                    $itemInfo = [];
                    $itemInfo["url"] = $fnpUrlFinished;
                    $itemInfo["tags"] = "v";
                    $item['bpInfos'][] = $itemInfo;
                }
                if (!empty($bpUrlProgress)) {
                    $itemInfo = [];
                    $itemInfo["url"] = $bpUrlProgress;
                    $itemInfo["tags"] = "p";
                    $item['bpInfos'][] = $itemInfo;
                }
                if (!empty($bpUrlFinished)) {
                    $itemInfo = [];
                    $itemInfo["url"] = $bpUrlFinished;
                    $itemInfo["tags"] = "v";
                    $item['bpInfos'][] = $itemInfo;
                }
                $item['descr'] = $this->getValue($hit, 'blp_description');
            } else {
                $bbox = [];
                $bbox[] = [reset($y1), reset($x1)];
                $bbox[] = [reset($y2), reset($x2)];
                $item['bbox'] = $bbox;
                $item['procedure'] = CodelistHelper::getCodelistEntry(['8001'], $this->getValue($hit, 't01_object.obj_class'), 'de');
                $categories = $this->getValue($hit, 'uvp_category');
                foreach ($categories as $category) {
                    $tmpArray = [];
                    $tmpArray['id'] = $category;
                    $tmpArray['name'] = $this->grav['language']->translate('SEARCH_RESULT.CATEGORIES_UVP_' . strtoupper($category));
                    $item['categories'][] = $tmpArray;
                }
                $steps = $this->getValue($hit, 'uvp_steps');
                foreach ($steps as $step) {
                    $item['steps'][] = $this->grav['language']->translate('SEARCH_DETAIL.STEPS_UVP_' . strtoupper($step));
                }
            }
            $items[] = $item;
        }
        return $items;
    }

    private function getValue($value, string $key): mixed
    {
        if (property_exists($value, $key)) {
            return $value -> $key;
        }
        return null;
    }

    public function isSortHitsEnable(): bool
    {
        $facetConfig = $this->grav['config']->get('themes.' . $this->theme . '.hit_search.facet_config') ?: [];
        foreach ($this->selectedFacets as $key => $param) {
            foreach ($facetConfig as $facet) {
                if ($facet['id'] === $key) {
                    if (isset($facet['display_sort_hits_on_selection']) && $facet['display_sort_hits_on_selection']) {
                        return true;
                    }
                    if (isset($facet['facets'])) {
                        foreach ($facet['facets'] as $subFacet) {
                            if (isset($subFacet['display_sort_hits_on_selection']) && $subFacet['display_sort_hits_on_selection']) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }
}