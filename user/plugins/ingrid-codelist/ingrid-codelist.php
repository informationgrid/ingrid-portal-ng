<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Grav\Common\Grav;
use Grav\Common\Data\Data;

/**
 * Class InGridCodelistPlugin
 * @package Grav\Plugin
 */
class InGridCodelistPlugin extends Plugin
{

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
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            $this->enable([
                'onAdminMenu' => ['onAdminMenu', 0],
                'onAdminTaskExecute' => ['onAdminTaskExecute', 0],
                'onTwigSiteVariables' => ['onTwigAdminVariables', 0],
                'onTwigLoader' => ['addAdminTwigTemplates', 0],
            ]);

            return;
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

    public function onSchedulerInitialized(Event $e): void
    {
        if ($this->config->get('plugins.ingrid-codelist.codelist.scheduled_index.enabled')) {
            /** @var Scheduler $scheduler */
            $scheduler = $e['scheduler'];
            $at = $this->config->get('plugins.ingrid-codelist.codelist.scheduled_index.at');
            $logs = $this->config->get('plugins.ingrid-codelist.codelist.scheduled_index.logs');
            $job = $scheduler->addCommand('bin/plugin', ['ingrid-codelist', 'index'], 'ingrid-codelist-index');
            $job->at($at);
            $job->output($logs);
            $job->backlink('/plugins/ingrid-codelist');
        }
    }

    /**
     * Handle the Reindex task from the admin
     *
     * @param Event $e
     */
    public function onAdminTaskExecute(Event $e): void
    {
        if ($e['method'] === 'taskReindexCodelist') {
            $controller = $e['controller'];

            header('Content-type: application/json');

            if (!$controller->authorizeTask('reindexCodelist', ['admin.configuration', 'admin.super'])) {
                $json_response = [
                    'status'  => 'error',
                    'message' => '<i class="fa fa-warning"></i> '. $this->grav['language']->translate(['PLUGIN_INGRID_CODELIST.INDEXING_CODELIST_EMPTY']),
                    'details' => $this->grav['language']->translate(['PLUGIN_INGRID_CODELIST.INDEXING_UNPERMISSION'])
                ];
                echo json_encode($json_response);
                exit;
            }

            // disable warnings
            error_reporting(1);
            // disable execution time
            set_time_limit(0);

            list($status, $msg, $output) = self::indexJob();

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

        [$status, $msg] = self::getCodelistCount();

        $twig->twig_vars['codelist_index_status'] = ['status' => $status, 'msg' => $msg];
        $this->grav['assets']->addCss('plugin://ingrid-codelist/assets/admin/codelist.css');
        $this->grav['assets']->addJs('plugin://ingrid-codelist/assets/admin/codelist.js');
    }

     /**
     * Add reindex button to the admin QuickTray
     */
    public function onAdminMenu(): void
    {
        $options = [
            'authorize' => 'taskReindexCodelist',
            'hint' => $this->grav['language']->translate(['PLUGIN_INGRID_CODELIST.GRAV_MENU_TEXT']),
            'class' => 'codelist-reindex',
            'icon' => 'fa-book'
        ];

        $this->grav['twig']->plugins_quick_tray['InGrid Codelist'] = $options;
    }

    public static function indexJob(): array
    {
        ob_start();
        $grav = Grav::instance();
        $codelist_config = new Data($grav['config']->get('plugins.ingrid-codelist.codelist.api'));

        [$status, $msg] = CodelistIndex::indexJob($codelist_config->get('url'), $codelist_config->get('user'), $codelist_config->get('pass'), $grav['language'], $grav['log']);
        $output = ob_get_clean();

        return [$status, $msg, $output];
    }

    private function getCodelistCount(): array
    {
        $path = 'user-data://codelists/codelists.json';
        $status = false;
        $msg = $this->grav['language']->translate(['PLUGIN_INGRID_CODELIST.INDEXING_CODELIST_EMPTY']);
        try {
            if(file_exists($path)) {
                $response = file_get_contents($path);
                $json = json_decode($response, true);
                if (isset($json['status'])) {
                    $jsonStatus = $json['status'];
                    if (isset($jsonStatus['error'])) {
                        $msg = $this->grav['language']->translate(['PLUGIN_INGRID_CODELIST.INDEXING_CODELIST_FAILED', $jsonStatus['error']]);
                        $msg .= ' '.$this->grav['language']->translate(['PLUGIN_INGRID_CODELIST.INDEXING_CODELIST_SUCCESS', count($json['data']), $jsonStatus['time']]);
                        $status = false;
                    } else {
                        $msg = $this->grav['language']->translate(['PLUGIN_INGRID_CODELIST.INDEXING_CODELIST_SUCCESS', count($json['data']), $jsonStatus['time']]);
                        $status = true;
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return [$status, $msg];
    }

}
