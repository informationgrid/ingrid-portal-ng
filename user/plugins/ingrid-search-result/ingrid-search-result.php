<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Twig\Twig;

/**
 * Class InGridSearchResultPlugin
 * @package Grav\Plugin
 */
class InGridSearchResultPlugin extends Plugin
{

    var SearchService $service;
    var int $hitsNum;

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                // Uncomment following line when plugin requires Grav < 1.7
                // ['autoload', 100000],
                ['onPluginsInitialized', 0]
            ]
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        $uri = $this->grav['uri'];
        $uri_path = $uri->path();
        $config = $this->config();
        $routes = $config['routes'] ?? [];
        if ($routes && in_array($uri_path, $routes)) {
            switch ($uri_path) {
                case '/':
                    // Startseite

                    $this->enable([
                        'onPageInitialized' => ['onPageInitialized', 0],
                        'onTwigSiteVariables' => ['onTwigSiteVariablesHome', 0],
                        'onTwigExtensions' => ['onTwigExtensions', 0],
                    ]);
                    break;

                case '/freitextsuche':
                    // Suche

                    $this->enable([
                        'onPageInitialized' => ['onPageInitialized', 0],
                        'onTwigSiteVariables' => ['onTwigSiteVariablesSearch', 0],
                        'onTwigExtensions' => ['onTwigExtensions', 0],
                    ]);
                    break;

                case '/informationsanbieter':
                    // Informationsanbieter

                    $this->hitsNum = 1000;
                    $this->service = new SearchServiceImpl($this->grav, $this->hitsNum, [], []);
                    $this->enable([
                        'onPageInitialized' => ['onPageInitialized', 0],
                        'onTwigSiteVariables' => ['onTwigSiteVariablesProviders', 0],
                    ]);
                    break;

                case '/kartendienste':
                    // UVP legend

                    $this->hitsNum = 0;
                    // Theme config
                    $theme = $this->grav['config']->get('system.pages.theme');
                    $facetConfig = $this->grav['config']->get('themes.' . $theme . '.map.leaflet.legend.facet_config') ?? [];
                    if ($theme === 'uvp') {
                        $this->service = new SearchServiceImpl($this->grav, $this->hitsNum, $facetConfig, []);
                        $this->enable([
                            'onPageInitialized' => ['onPageInitialized', 0],
                            'onTwigSiteVariables' => ['onTwigSiteVariablesMapLegend', 0],
                        ]);
                    }
                    break;

                case '/map/mapMarker':
                    // UVP markers

                    // Theme config
                    $theme = $this->grav['config']->get('system.pages.theme');
                    $this->hitsNum = $this->grav['config']->get('themes.' . $theme . '.map.leaflet.legend.marker_num');
                    if ($theme === 'uvp') {
                        $this->service = new SearchServiceImpl($this->grav, $this->hitsNum, [], []);
                        $this->enable([
                            'onPageInitialized' => ['onPageInitialized', 0],
                            'onTwigSiteVariables' => ['onTwigSiteVariablesMapMarkers', 0],
                        ]);
                    }
                    break;
            }
        }
    }

    public function onPageInitialized(): void
    {
    }

    public function onTwigSiteVariablesSearch(): void
    {

        if (!$this->isAdmin()) {
            $query = $this->grav['uri']->query('q') ?: '';
            $page = $this->grav['uri']->query('page') ?: 1;
            $rootUrl = $this->grav['uri']->rootUrl();
            $ranking = $this->grav['uri']->query('ranking') ?: '';
            $lang = $this->grav['language']->getLanguage();

            $config = $this->config();
            // Plugin config
            $this->hitsNum = $config['hit_search']['hits_num'];
            $facetConfig = $config['hit_search']['facet_config'];
            $excludeFromSearch = $config['hit_search']['exclude_from_search'] ?? [];

            // Theme config
            $theme = $this->grav['config']->get('system.pages.theme');
            $facetConfig = $this->grav['config']->get('themes.' . $theme . '.hit_search.facet_config') ?? $facetConfig;
            $this->hitsNum = $this->grav['config']->get('themes.' . $theme . '.hit_search.hits_num') ?? $this->hitsNum;
            $excludeFromSearch = $this->grav['config']->get('themes.' . $theme . '.hit_search.exclude_from_search') ?? $excludeFromSearch;

            $sortByDate = $this->grav['config']->get('themes.' . $theme . '.hit_search.sort.sortByDate') ?? 0;
            if (!empty($ranking)) {
                if ($ranking === 'date') {
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
            $this->service = new SearchServiceImpl($this->grav, $this->hitsNum, $facetConfig, $excludeFromSearch, $sortByDate);

            $selectedFacets = $this->getSelectedFacets();
            $results = $this->service->getSearchResults($query, $page, $selectedFacets, $this->grav['uri'], $lang, $theme);

            $this->grav['twig']->twig_vars['query'] = $query;
            $this->grav['twig']->twig_vars['selected_facets'] = $selectedFacets;
            $this->grav['twig']->twig_vars['facetMapCenter'] = array(51.3, 10, 5);
            $this->grav['twig']->twig_vars['search_result'] = $results;
            $this->grav['twig']->twig_vars['rootUrl'] = $rootUrl;
            $this->grav['twig']->twig_vars['hitsNum'] = $this->hitsNum;
            $this->grav['twig']->twig_vars['pagingUrl'] = $this->getPagingUrl($this->grav['uri']);
            $this->grav['twig']->twig_vars['search_ranking'] = $ranking;

        }
    }

    public function onTwigSiteVariablesHome(): void
    {

        if (!$this->isAdmin()) {
            $lang = $this->grav['language']->getLanguage();

            $config = $this->config();
            // Plugin config
            $facetConfig = $config['categories']['facet_config'];
            $excludeFromCategoriesSearch = $config['categories']['exclude_from_search'] ?? [];
            $excludeFromHitsSearch = $config['hits']['exclude_from_search'] ?? [];

            // Theme config
            $theme = $this->grav['config']->get('system.pages.theme');
            $facetConfig = $this->grav['config']->get('themes.' . $theme . '.home.categories.facet_config') ?? $facetConfig;
            $excludeFromCategoriesSearch = $this->grav['config']->get('themes.' . $theme . '.home.categories.exclude_from_search') ?? $excludeFromCategoriesSearch;
            $excludeFromHitsSearch = $this->grav['config']->get('themes.' . $theme . '.home.hits.exclude_from_search') ?? $excludeFromHitsSearch;

            $this->service = new SearchServiceImpl($this->grav, 0, $facetConfig, $excludeFromCategoriesSearch);
            $categories_result = $this->service->getSearchResults("", 1, [], $this->grav['uri'], $lang, $theme);
            $this->grav['twig']->twig_vars['categories_result'] = $categories_result;

            $this->hitsNum = $this->grav['config']->get('themes.' . $theme . '.home.hits.num') ?? 0;
            $sortByDate = $this->grav['config']->get('themes.' . $theme . '.home.hits.sort.sortByDate') ?? 0;

            if ($this->hitsNum > 0) {
                $this->service = new SearchServiceImpl($this->grav, $this->hitsNum, [], $excludeFromHitsSearch, $sortByDate);
                $hits_result = $this->service->getSearchResults("", 1, [], $this->grav['uri'], $lang, $theme);
                $this->grav['twig']->twig_vars['hits_result'] = $hits_result;
            }
        }
    }

    public function onTwigSiteVariablesProviders(): void
    {

        if (!$this->isAdmin()) {
            // Theme config
            $theme = $this->grav['config']->get('system.pages.theme');
            $query = $this->grav['config']->get('themes.' . $theme . '.hit_providers.query');
            if ($query) {
                $lang = $this->grav['language']->getLanguage();
                $results = $this->service->getSearchResultOriginalHits($query, 1, []);
                $this->grav['twig']->twig_vars['partners'] = self::getPartners($results);
            } else {
                $partners = CodelistHelper::getCodelistPartnerProviders();
                $this->grav['twig']->twig_vars['partners'] = $partners;
            }
        }
    }

    public function onTwigSiteVariablesMapLegend(): void
    {
        if (!$this->isAdmin()) {
            // Theme config
            $theme = $this->grav['config']->get('system.pages.theme');
            $lang = $this->grav['language']->getLanguage();
            $results = $this->service->getSearchResults("", 1, [], $this->grav['uri'], $lang);
            if ($results) {
                $this->grav['twig']->twig_vars['legend'] = json_encode($results->facets);
                $this->grav['twig']->twig_vars['requestLayer'] = $this->grav['uri']->query('layer') ?: "";
                $this->grav['twig']->twig_vars['mapParamE'] = $this->grav['uri']->query('E') ?: "";
                $this->grav['twig']->twig_vars['mapParamN'] = $this->grav['uri']->query('N') ?: "";
                $this->grav['twig']->twig_vars['mapParamZoom'] = $this->grav['uri']->query('zoom') ?: "";
                $this->grav['twig']->twig_vars['mapParamExtent'] = $this->grav['uri']->query('extent') ?: "";
            }
        }
    }

    public function onTwigSiteVariablesMapMarkers(): void
    {
        if (!$this->isAdmin()) {
            $lang = $this->grav['language']->getLanguage();

            // Theme config
            $theme = $this->grav['config']->get('system.pages.theme');
            $type = $this->grav['uri']->query('type') ?: "";
            $page = $this->grav['uri']->query('page') ?: "";
            $output = [];
            if (!empty($type)) {
                $facetConfig = $this->grav['config']->get('themes.' . $theme . '.map.leaflet.legend.facet_config') ?? [];
                if (count($facetConfig) > 0) {
                    $facet = $facetConfig[0];
                    $queries = $facet['queries'];
                    if (isset($queries['obj_class_' . $type])) {
                        $query = $queries['obj_class_' . $type];
                        if ($query) {
                            if(isset($query['search']))
                            $hits = $this->service->getSearchResultOriginalHits($query['search'], (int) $page, []);
                            if ($hits) {
                                $output = self::getMapMarkers($hits, $type);
                            }
                        }
                    }

                }
            }
            echo json_encode($output);
            exit;
        }
    }

    public function onTwigExtensions(): void
    {
        require_once(__DIR__ . '/twig/SearchResultHitTwigExtension.php');
        $this->grav['twig']->twig->addExtension(new SearchResultHitTwigExtension());
    }

    private function getSelectedFacets(): array
    {
        $query_params = $this->grav['uri']->query(null, true);
        if (isset($query_params['q'])) {
            unset($query_params['q']);
        }
        if (isset($query_params['more'])) {
            unset($query_params['more']);
        }
        if (isset($query_params['page'])) {
            unset($query_params['page']);
        }
        if (isset($query_params['ranking'])) {
            unset($query_params['ranking']);
        }
        return $query_params;
    }

    private function getPagingUrl(mixed $uri): string
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

    private function getPartners(array $results): array
    {
        $list = array();
        $partners = CodelistHelper::getCodelistPartners();
        foreach ($partners as $partner) {
            $newPartner = [
                'name' => $partner['name'],
                'ident' => $partner['ident'],
            ];
            $providerList = array();
            foreach ($results as $result) {
                $hit = $result->_source;
                $hitPartners = $hit->partner;
                $name = self::getValue($hit, 'title');
                $parentName = self::getValue($hit, 't02_address.parents.title');
                if ($parentName) {
                    if (is_array($parentName)) {
                        foreach ($parentName as $parentTitle) {
                            $name = $parentTitle . '<br>' . $name;
                        }
                    } else {
                        $name = $parentName . '<br>' . $name;
                    }
                }
                $exists = in_array($newPartner['ident'], $hitPartners);
                if ($exists && !empty($name)) {
                    $providerExists = in_array($name, array_column($providerList, 'name'));
                    if ( $providerExists === false) {
                        $url = null;
                        $commTypeKeys = self::getValue($hit, 't021_communication.commtype_key');
                        if (is_array($commTypeKeys)) {
                            $existsCommTypeKey = array_search('4', $commTypeKeys);
                            if ($existsCommTypeKey !== false) {
                                $commValues = self::getValue($hit, 't021_communication.comm_value');
                                $url = $commValues[$existsCommTypeKey];
                            }
                        } else {
                            if ($commTypeKeys == '4') {
                                $commValues = self::getValue($hit, 't021_communication.comm_value');
                                $url = $commValues;
                            }
                        }
                        if (!empty($url) && !str_starts_with($url, 'http')) {
                            $url = 'https://' . $url;
                        }
                        $provider = [
                            'name' => $name,
                            'url' => $url,
                        ];
                        $providerList[] = $provider;
                    }
                }
            }
            $newPartner['providers'] = $providerList;
            $list[] = $newPartner;
        }
        return $list;
    }

    private function getMapMarkers(array $hits, $type): array
    {
        $items = [];
        foreach ($hits as $source) {
            $hit = $source->_source;
            $item = [];
            $item['title'] = self::getValue($hit, 'blp_name') ?? self::getValue($hit, 'title');
            $item['lat'] = self::getValue($hit, 'lat_center') ?? self::getValue($hit, 'y1');
            $item['lon'] = self::getValue($hit, 'lon_center') ?? self::getValue($hit, 'x1');;
            $item['iplug'] = self::getValue($hit, 'iPlugId');
            $item['uuid'] = self::getValue($hit, 't01_object.obj_id');
            if (str_contains('blp', $type)) {
                $item['isBLP'] = true;
                $item['bpInfos'] = [];
                $blpUrlFinished = self::getValue($hit, 'blp_url_finished');
                $blpUrlProgress = self::getValue($hit, 'blp_url_in_progress');
                $fnpUrlFinished = self::getValue($hit, 'fnp_url_finished');
                $fnpUrlProgress = self::getValue($hit, 'fnp_url_in_progress');
                $bpUrlFinished = self::getValue($hit, 'bp_url_finished');
                $bpUrlProgress = self::getValue($hit, 'bp_url_in_progress');

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
                $item['descr'] = self::getValue($hit, 'blp_description');
            } else {
                $item['procedure'] = CodelistHelper::getCodelistEntry(['8001'], self::getValue($hit, 't01_object.obj_class'), 'de');
                $categories = self::getValue($hit, 'uvp_category');
                foreach ($categories as $category) {
                    $tmpArray = [];
                    $tmpArray['id'] = $category;
                    $tmpArray['name'] = $this->grav['language']->translate('SEARCH_RESULT.CATEGORIES_UVP_' . strtoupper($category));
                    $item['categories'][] = $tmpArray;
                }
                $steps = self::getValue($hit, 'uvp_steps');
                foreach ($steps as $step) {
                    $item['steps'][] = $this->grav['language']->translate('SEARCH_RESULT.STEPS_UVP_' . strtoupper($step));
                }
            }
            $items[] = $item;
    }
        return $items;
    }

    private static function getValue($value, string $key): mixed
    {
        if (property_exists($value, $key)) {
            return $value -> $key;
        }
        return null;
    }
}
