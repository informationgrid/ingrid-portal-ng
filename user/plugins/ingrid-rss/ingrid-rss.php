<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class IngridRssPlugin
 * @package Grav\Plugin
 */
class IngridRssPlugin extends Plugin
{
    /**
     * @var array
     */
    protected $rss_feeds;

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
            'onSchedulerInitialized'    => ['onSchedulerInitialized', 0],
            'onTwigLoader'              => ['onTwigLoader', 0],
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
        $config = $this->config();

        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            $this->rss_feeds = $this->config->get('plugins.ingrid-rss.rss.feeds.links');

            $this->enable([
                'onAdminMenu' => ['onAdminMenu', 0],
                'onAdminTaskExecute' => ['onAdminTaskExecute', 0],
                'onTwigSiteVariables' => ['onTwigAdminVariables', 0],
                'onTwigLoader' => ['addAdminTwigTemplates', 0],
            ]);

            return;
        }

        $uri = $this->grav['uri'];

        $routes = $config['routes'] ?? [];
        foreach($routes as $route) {
            if ($route && $route == $uri->path()) {
                $this->enable([
                    'onPageInitialized' => ['onPageInitialized', 0],
                    'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
                ]);
            }
        }
    }

    public function onPageInitialized(): void
    {
        if (!$this->isAdmin()) {
            $results = RssResult::getResults();

            $this->grav['twig']->twig_vars['rss_feeds'] = $results;
        }
    }

    /**
     * Add the Twig template paths to the Twig laoder
     */
    public function onTwigLoader(): void
    {
        $this->grav['twig']->addPath(__DIR__ . '/templates');
    }

    /**
     * Add the current template paths to the admin Twig loader
     */
    public function addAdminTwigTemplates(): void
    {
        $this->grav['twig']->addPath($this->grav['locator']->findResource('theme://templates'));
    }

    public function onTwigSiteVariables(): void
    {

    }


    public function onSchedulerInitialized(Event $e): void
    {
        if ($this->config->get('plugins.ingrid-rss.rss.scheduled_index.enabled')) {
            /** @var Scheduler $scheduler */
            $scheduler = $e['scheduler'];
            $at = $this->config->get('plugins.ingrid-rss.rss.scheduled_index.at');
            $logs = $this->config->get('plugins.ingrid-rss.rss.scheduled_index.logs');
            $job = $scheduler->addCommand('bin/plugin', ['ingrid-rss', 'index'], 'ingrid-rss-index');
            $job->at($at);
            $job->output($logs);
            $job->backlink('/plugins/ingrid-rss');
        }
    }

    /**
     * Handle the Reindex task from the admin
     *
     * @param Event $e
     */
    public function onAdminTaskExecute(Event $e): void
    {
        if ($e['method'] === 'taskReindexRss') {
            $controller = $e['controller'];

            header('Content-type: application/json');

            if (!$controller->authorizeTask('reindexRss', ['admin.configuration', 'admin.super'])) {
                $json_response = [
                    'status'  => 'error',
                    'message' => '<i class="fa fa-warning"></i> Index not created',
                    'details' => 'Insufficient permissions to reindex the search engine database.'
                ];
                echo json_encode($json_response);
                exit;
            }

            // disable warnings
            error_reporting(1);
            // disable execution time
            set_time_limit(0);

            list($status, $msg, $output) = static::indexJob($this->rss_feeds);

            $json_response = [
                'status'  => $status ? 'success' : 'error',
                'message' => $msg
            ];

            echo json_encode($json_response);
            exit;

        }

    }

    /**
     * Set some twig vars and load CSS/JS assets for admin
     */
    public function onTwigAdminVariables(): void
    {
        $twig = $this->grav['twig'];

        [$status, $msg] = self::getRssCount();

        $twig->twig_vars['index_status'] = ['status' => $status, 'msg' => $msg];
        $this->grav['assets']->addCss('plugin://ingrid-rss/assets/admin/rss.css');
        $this->grav['assets']->addJs('plugin://ingrid-rss/assets/admin/rss.js');
    }

     /**
     * Add reindex button to the admin QuickTray
     */
    public function onAdminMenu(): void
    {
        $options = [
            'authorize' => 'taskReindexRss',
            'hint' => 'reindexes the RSS index',
            'class' => 'rss-reindex',
            'icon' => 'fa-rss'
        ];

        $this->grav['twig']->plugins_quick_tray['InGrid RSS'] = $options;
    }

    public function indexJob(array $rss_feeds, string $langCode = null)
    {
        ob_start();

        $results = RssIndex::indexJob($rss_feeds);

        $output = ob_get_clean();
        [$status, $msg] = self::getRssCount();

        return [$status, $msg, $output];
    }

    private function getRssCount(): array
    {
        $path = 'user-data://feeds/feeds.json';
        $status = false;
        $msg = 'Index not created';
        try {
            if(file_exists($path)) {
                $response = file_get_contents($path);
                $json = json_decode($response, true);
                $msg = '';
                $msg .=  count($json["data"]) . ' RSS feeds reindexed on '. $json["status"]["time"];
            }
        } catch (Exception $e) {
        }

        return [$status, $msg];
    }
}