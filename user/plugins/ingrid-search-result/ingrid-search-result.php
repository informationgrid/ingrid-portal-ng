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
        $this->service = $config['mocking']
            ? new SearchServiceMock()
            : new SearchServiceImpl($this->grav);
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

    public function onTwigSiteVariables()
    {

        if (!$this->isAdmin()) {
            $query = $this->grav['uri']->query('q') ?: "";
            $page = $this->grav['uri']->query('page') ?: 0;
            $selectedFacets = $this->getSelectedFacets();
            $results = $this->service->getSearchResults($query, $page, $selectedFacets, $this->grav['uri']);
            $this->grav['twig']->twig_vars['query'] = $query;
            $this->grav['twig']->twig_vars['facets_config'] = $this->grav['config']->get('plugins.ingrid-search-result.facet_config');
            $this->grav['twig']->twig_vars['selected_facets'] = $selectedFacets;
            $this->grav['twig']->twig_vars['facetMapCenter'] = array(51.3, 10, 5);

            $this->grav['twig']->twig_vars['search_result'] = $results;
        }
    }

    public function onTwigExtensions()
    {
        require_once(__DIR__ . '/twig/SearchResultHitTwigExtension.php');
        $this->grav['twig']->twig->addExtension(new SearchResultHitTwigExtension());
    }

    private
    function getSelectedFacets()
    {
        $query_params = $this->grav['uri']->query(null, true);
        if (isset($query_params['q'])) {
            unset($query_params['q']);
        }
        return $query_params;
    }

    /**
     * @param array $inputOptions
     * @return string
     */
    public
    function getCoordinates(array $inputOptions): string
    {
        $coords = "";
        if (property_exists((object)$inputOptions, "x1") && $inputOptions["x1"] != "") {
            $coords = "&coords=" . "x1:" . $inputOptions["x1"] . ",y1:" . $inputOptions["y1"] . ",x2:" . $inputOptions["x2"] . ",y2:" . $inputOptions["y2"];
        }
        return $coords;
    }

    /**
     * @param array $inputOptions
     * @return array
     */
    public
    function cleanupParameters(array $inputOptions): array
    {
        unset($inputOptions["x1"]);
        unset($inputOptions["y1"]);
        unset($inputOptions["x2"]);
        unset($inputOptions["y2"]);
        unset($inputOptions["areaid"]);
        unset($inputOptions["action"]);
        return $inputOptions;
    }

}
