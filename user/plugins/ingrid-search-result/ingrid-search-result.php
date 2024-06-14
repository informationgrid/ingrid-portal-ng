<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Twig\Twig;

/**
 * Class IngridSearchResultPlugin
 * @package Grav\Plugin
 */
class IngridSearchResultPlugin extends Plugin
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
        $uri = $this->grav['uri'];
        if ($uri->path() === '/search' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
        }
    }

    public function onTwigExtensions()
    {
    }

    private function handleCheckboxSubmission(): void
    {
        $inputOptions = $_POST;

        $uri = $this->grav['uri'];
        // Get the full current URL without query parameters
        $base_url = $uri->path();

        $query_string = array();
        foreach ($inputOptions as $key => $value) {
            $params[$key] = $value;  // Add or update the 'newparam'

            // Check if the new parameter is already there to avoid endless redirection
            if ($uri->param($key) !== $value) {
                // Build the new query string with all parameters
                $query_string[] = http_build_query($params);
            }
        }
        // Construct the new URL with the updated query string
        $new_url = $base_url . '?' . join('&', $query_string);

        // Redirect to the new URL
        $this->grav->redirect($new_url);
    }

    private function getSelectedFacets()
    {
        return $this->grav['uri']->query(null, true);
    }

}
