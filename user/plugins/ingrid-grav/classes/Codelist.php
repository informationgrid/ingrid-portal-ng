<?php

namespace Grav\Plugin;

use Grav\Common\Grav;
use RocketTheme\Toolbox\Event\Event;

class Codelist
{
    var Grav $grav;

    var string $lang;

    public function __construct(Grav $grav) {
        $this->grav = $grav;
        $this->lang = $grav['language']->getLanguage();
    }

    public function getCount(): array
    {
        $path = 'user-data://codelists/codelists.json';
        $status = false;
        $msg = $this->grav['language']->translate(['PLUGIN_INGRID_GRAV.CODELIST_API.INDEXING_CODELIST_EMPTY']);
        try {
            if (file_exists($path)) {
                $response = file_get_contents($path);
                $json = json_decode($response, true);
                if (isset($json['status'])) {
                    $jsonStatus = $json['status'];
                    if (isset($jsonStatus['error'])) {
                        $msg = $this->grav['language']->translate(['PLUGIN_INGRID_GRAV.CODELIST_API.INDEXING_CODELIST_FAILED', $jsonStatus['error']]);
                        $msg .= ' ' . $this->grav['language']->translate(['PLUGIN_INGRID_GRAV.CODELIST_API.INDEXING_CODELIST_SUCCESS', count($json['data']), $jsonStatus['time']]);
                        $status = false;
                    } else {
                        $msg = $this->grav['language']->translate(['PLUGIN_INGRID_GRAV.CODELIST_API.INDEXING_CODELIST_SUCCESS', count($json['data']), $jsonStatus['time']]);
                        $status = true;
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return [$status, $msg];
    }

    public function taskReindex(Event $e): void
    {
        $controller = $e['controller'];

        header('Content-type: application/json');

        if (!$controller->authorizeTask('reindexCodelist', ['admin.configuration', 'admin.super'])) {
            $json_response = [
                'status'  => 'error',
                'message' => '<i class="fa fa-warning"></i> '. $this->grav['language']->translate(['PLUGIN_INGRID_GRAV.CODELIST_API.INDEXING_CODELIST_EMPTY']),
                'details' => $this->grav['language']->translate(['PLUGIN_INGRID_GRAV.CODELIST_API.INDEXING_UNPERMISSION'])
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
        $config = $grav['config']->get('plugins.ingrid-grav.codelist_api');
        [$status, $msg] = CodelistIndex::indexJob(
            $config['url'],
            $config['user'],
            $config['pass']
        );
        $output = ob_get_clean();

        return [$status, $msg, $output];
    }

    public function setScheduler(Event $e): void
    {
        $config = $this->grav['config']->get('plugins.ingrid-grav.codelist_api.scheduled_index');
        if ($config['enabled']) {
            /** @var Scheduler $scheduler */
            $scheduler = $e['scheduler'];
            $at = $config['at'];
            $logs = $config['logs'];
            $job = $scheduler->addCommand('bin/plugin', ['ingrid-grav', 'index-codelist'], 'ingrid-codelist-index');
            $job->at($at);
            $job->output($logs);
            $job->backlink('/plugins/ingrid-grav');
        }
    }

}