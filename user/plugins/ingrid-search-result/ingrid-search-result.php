<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\GPM\Response;
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
        $this->service = $config['mocking'] ? new SearchServiceMock($this->grav) : new SearchServiceImpl($this->grav);
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
            $query = "";
            $tmpQuery = $this->grav['uri']->query('q');
            if (!is_null($tmpQuery)) {
                $query = $tmpQuery;
            }
            $results = $this->service->getSearchResults($query);

            $this->grav['twig']->twig_vars['search_result_query'] = $query;
            $this->grav['twig']->twig_vars['search_result_numOfHits'] = $results->getNumOfHits();
            $this->grav['twig']->twig_vars['search_result_numOfPages'] = $results->getNumOfPages();
            $this->grav['twig']->twig_vars['search_result_numPage'] = $results->getNumPage();
            $this->grav['twig']->twig_vars['search_result_hits'] = $results->getHits();
        }
    }

    public function onTwigExtensions()
    {
        require_once(__DIR__ . '/twig/IngridSearchResultHitTwigExtension.php');
        $this->grav['twig']->twig->addExtension(new IngridSearchResultHitTwigExtension());
    }

}
