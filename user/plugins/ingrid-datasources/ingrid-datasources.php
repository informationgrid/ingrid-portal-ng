<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;

/**
 * Class InGridDatasourcesPlugin
 * @package Grav\Plugin
 */
class InGridDatasourcesPlugin extends Plugin
{

    var string $api_url;
    var array $excludes;

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
            ],
            'onTwigLoader'              => ['onTwigLoader', 0]
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

        $config = $this->config();
        if ($this->isAdmin()) {
            $this->api_url = getenv('INGRID_API') !== false ?
                getenv('INGRID_API') . 'portal/catalogs' : $config['ingrid_api_url'];
                $this->enable([
                'onTwigSiteVariables' => ['onTwigAdminVariables', 0]
            ]);
            return;
        }

        $uri = $this->grav['uri'];
        $route = $config['route'] ?? null;
        if ($route && $route == $uri->path()) {
            $this->api_url = getenv('INGRID_API') !== false ?
                getenv('INGRID_API') . 'portal/catalogs' : $config['ingrid_api_url'];
            if (array_key_exists('excludes', $config) && !is_null($config['excludes']) ) {
                $this->excludes = $config['excludes'];
            }
            $this->enable([
                'onPageInitialized' => ['onPageInitialized', 0],
                'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
        ]);
    }
    }

    public function onPageInitialized(): void
    {
        echo "<script>console.log('Datasources');</script>";
    }

        /**
     * Add the Twig template paths to the Twig laoder
     */
    public function onTwigLoader(): void
    {
        $this->grav['twig']->addPath(__DIR__ . '/templates');
    }

    public function onTwigAdminVariables()
    {
        if ($this->isAdmin()) {
            $response = file_get_contents($this->api_url);
            $items = json_decode($response, true);
            $fieldSelectize = self::getAdminSelectizes($items);
            $this->grav['twig']->twig_vars['datasources'] = $fieldSelectize;
        }
    }

    public function onTwigSiteVariables()
    {
        if (!$this->isAdmin()) {
            $response = file_get_contents($this->api_url);
            $items = json_decode($response, true);
            $plugs = self::getDatasources($items, $this->excludes ?? []);
            $this->grav['twig']->twig_vars['plugs'] = $plugs;
        }
    }

    private function getDatasources($items, $excludes = [])
    {
        $list = array();
        foreach ($items as $item) {
            if (array_key_exists('name', $item)) {
                $name = $item['name'];
                if ($name) {
                    $exists = array_search($name, $list);
                    $toExclude = array_search($name, $excludes);
                    if ($exists === false && $toExclude === false) {
                        array_push($list, $name);
                    }
                }
            }
        }
        return $list;
    }

    private function getAdminSelectizes($items)
    {
        $list = array();
        foreach ($items as $item) {
            if (array_key_exists('name', $item)) {
                $name = $item['name'];
                if ($name) {
                    $exists = array_search($name, $list);
                    if ($exists === false) {
                        $entry = [];
                        $entry['text'] = $name;
                        $entry['value'] = $name;
                        array_push($list, $entry);
                    }
                }
            }
        }
        return $list;
    }
}
