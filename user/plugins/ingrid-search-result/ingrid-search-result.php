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

        $config = $this->config();
        $this->service = new SearchServiceImpl($this->grav);
        $uri = $this->grav['uri'];

        $route = $config['route'] ?? null;
        if ($route && $route == $uri->path()) {
            // Enable the main events we are interested in
            $this->enable([
                'onPageInitialized' => ['onPageInitialized', 0],
                'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
                'onTwigExtensions' => ['onTwigExtensions', 0],
            ]);
        }
    }

    public function onPageInitialized(): void
    {
        echo "<script>console.log('InGrid Search result');</script>";
    }

    public function onTwigSiteVariables(): void
    {

        if (!$this->isAdmin()) {
            $query = $this->grav['uri']->query('q') ?: "";
            $page = $this->grav['uri']->query('page') ?: 1;
            $rootUrl = $this->grav['uri']->rootUrl();
            $lang = $this->grav['language']->getLanguage();
            $selectedFacets = $this->getSelectedFacets();
            $results = $this->service->getSearchResults($query, $page, $selectedFacets, $this->grav['uri'], $lang);
            $hitsNum = $this->grav['config']->get('plugins.ingrid-search-result.hits_num');
            $this->grav['twig']->twig_vars['query'] = $query;
            $this->grav['twig']->twig_vars['facets_config'] = $this->grav['config']->get('plugins.ingrid-search-result.facet_config');
            $this->grav['twig']->twig_vars['selected_facets'] = $selectedFacets;
            $this->grav['twig']->twig_vars['facetMapCenter'] = array(51.3, 10, 5);

            $this->grav['twig']->twig_vars['search_result'] = $results;
            $this->grav['twig']->twig_vars['rootUrl'] = $rootUrl;
            $this->grav['twig']->twig_vars['pagingUrl'] = $this->getPagingUrl($this->grav['uri']);
            $this->grav['twig']->twig_vars['hitsNum'] = $hitsNum;
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
        $query_string[] = http_build_query($query_params);

        $url .= '?' . join('&', $query_string);
        return $url;
    }
}
