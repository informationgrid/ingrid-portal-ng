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
        if (isset($_POST['removeFacet'])) {
            $this->removeFacet();
        } else if ($this->grav['page']->slug() === 'search' && $_SERVER['REQUEST_METHOD'] === 'POST' && $this->grav['uri']->post("q")) {
            $this->handleSearchterm();
        } else if ($this->grav['page']->slug() === 'search' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCheckboxSubmission();
        }
    }

    public function onTwigSiteVariables()
    {

        if (!$this->isAdmin()) {
            $query = $this->grav['uri']->query('q') ?: "";
            $page = $this->grav['uri']->query('page') ?: 0;
            $results = $this->service->getSearchResults($query, $page, $this->getSelectedFacets());
            $this->grav['twig']->twig_vars['search_result'] = $results;
            $this->grav['twig']->twig_vars['query'] = $query;
            $this->grav['twig']->twig_vars['facets_config'] = $this->grav['config']->get('plugins.ingrid-search-result.facet_config');
            $this->grav['twig']->twig_vars['selected_facets'] = $this->getSelectedFacets();
            $this->grav['twig']->twig_vars['facetMapCenter'] = array(51.3, 10, 5);
        }
    }

    public function onTwigExtensions()
    {
        require_once(__DIR__ . '/twig/InGridSearchResultHitTwigExtension.php');
        $this->grav['twig']->twig->addExtension(new InGridSearchResultHitTwigExtension());
    }

    private function handleSearchterm(): void
    {
        $uri = $this->grav['uri'];
        $base_url = $uri->path();
        $search_term = $uri->post("q");
        $query_params = $uri->query(null, true);
        $query_params['q'] = $search_term;

        // Rebuild the query string
        $new_url = $base_url . '?' . http_build_query($query_params);
        $this->grav->redirect($new_url);
    }

    private function removeFacet(): void
    {
        $uri = $this->grav['uri'];
        $base_url = $uri->path();
        $query_params = $uri->query(null, true);

        $post_params = $_POST;
        unset($post_params['removeFacet']);

        foreach ($post_params as $key => $value) {
            if (isset($query_params[$key])) {
                if (is_array($query_params[$key])) {
                    foreach ($query_params[$key] as $index => $item) {
                        if ($item == $value) {
                            unset($query_params[$key][$index]);
                            // reset array so that index starts at 0
                            $query_params[$key] = array_values($query_params[$key]);
                        }
                    }
                } else {
                    unset($query_params[$key]);
                }
            }
        }

        // Rebuild the query string
        $new_url = $base_url . '?' . http_build_query($query_params);
        $this->grav->redirect($new_url);
    }

    private
    function handleCheckboxSubmission(): void
    {
        $inputOptions = $_POST;

        $uri = $this->grav['uri'];
        // Get the full current URL without query parameters
        $base_url = $uri->path();
        $search_term = $uri->post("q") ? "" : '&q=' . $uri->query("q");

        $query_string = array();

        $coords = $this->getCoordinates($inputOptions);
        $inputOptions = $this->cleanupParameters($inputOptions);
        // Build the new query string with all parameters
        // TODO: add each part individually in order to join them with "&"
        $query_string[] = http_build_query($inputOptions) . $coords . $search_term;
        // Construct the new URL with the updated query string
        $new_url = $base_url . '?' . join('&', $query_string);

        // Redirect to the new URL
        $this->grav->redirect($new_url);
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
