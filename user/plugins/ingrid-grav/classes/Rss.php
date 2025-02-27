<?php

namespace Grav\Plugin;

use Grav\Common\Grav;
use RocketTheme\Toolbox\Event\Event;

class Rss
{
    public Grav $grav;

    public function __construct(Grav $grav) {
        $this->grav = $grav;
    }

    public function getContent(): array
    {
        return RssResult::getResults();
    }

    public function getCount(): array
    {
        $path = 'user-data://feeds/feeds.json';
        $status = false;
        $lang = $this->grav['language'];
        $msg = $lang->translate(['PLUGIN_INGRID_GRAV.RSS.INDEXING_RSS_EMPTY']);
        try {
            if(file_exists($path)) {
                $response = file_get_contents($path);
                $json = json_decode($response, true);
                $msg = $lang->translate(['PLUGIN_INGRID_GRAV.RSS.INDEXING_RSS_SUCCESS', count($json["data"]), $json["status"]["time"]]);
                $status = true;
            }
        } catch (\Exception $e) {
        }

        return [$status, $msg];
    }

    public function taskReindex(Event $e): void
    {
        $controller = $e['controller'];

        header('Content-type: application/json');

        if (!$controller->authorizeTask('reindexRss', ['admin.configuration', 'admin.super'])) {
            $json_response = [
                'status'  => 'error',
                'message' => '<i class="fa fa-warning"></i> '. $this->grav['language']->translate(['PLUGIN_INGRID_GRAV.RSS.INDEXING_RSS_EMPTY']),
                'details' => $this->grav['language']->translate(['PLUGIN_INGRID_GRAV.RSS.INDEXING_UNPERMISSION'])
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

    public static function indexJob(): array
    {
        ob_start();
        $grav = Grav::instance();
        $theme = $grav['config']->get('system.pages.theme');
        $rss_feeds = $grav['config']->get('themes.' . $theme . '.rss.feeds') ?: [];
        RssIndex::indexJob($rss_feeds);

        $output = ob_get_clean();
        [$status, $msg] = (new Rss($grav))->getCount();

        return [$status, $msg, $output];
    }

    public function setScheduler(Event $e): void
    {
        $config = $this->grav['config']->get('plugins.ingrid-grav.rss.scheduled_index');
        if ($config['enabled']) {
            /** @var Scheduler $scheduler */
            $scheduler = $e['scheduler'];
            $at = $config['at'];
            $logs = $config['logs'];
            $job = $scheduler->addCommand('bin/plugin', ['ingrid-grav', 'index-rss'], 'ingrid-rss-index');
            $job->at($at);
            $job->output($logs);
            $job->backlink('/plugins/ingrid-grav');
        }
    }
}