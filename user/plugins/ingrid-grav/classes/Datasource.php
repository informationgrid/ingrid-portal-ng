<?php

namespace Grav\Plugin;

use Grav\Common\Grav;
use GuzzleHttp\Client;

class Datasource
{
    var Grav $grav;
    var string $configApi;
    var array $excludes;

    public function __construct(Grav $grav, string $api)
    {
        $this->grav = $grav;
        $this->configApi = $api;

        $theme = $this->grav['config']->get('system.pages.theme');
        $this->excludes = $this->grav['config']->get('themes.' . $theme . '.datasource.excludes') ?: [];
    }

    public function getContent(): array
    {
        $list = array();

        if (($response = @file_get_contents($this->configApi)) !== false) {
            $items = json_decode($response, true);
            foreach ($items as $item) {
                if (array_key_exists('name', $item)) {
                    $name = $item['name'];
                    if ($name) {
                        $exists = in_array($name, $list);
                        $toExclude = in_array($name, $this->excludes);
                        if ($exists === false && $toExclude === false) {
                            $list[] = $name;
                        }
                    }
                }
            }
        }
        return $list;
    }

    public function getAdminContent(): array
    {
        $list = array();

        if (($response = @file_get_contents($this->configApi)) !== false) {
            $items = json_decode($response, true);
            foreach ($items as $item) {
                if (array_key_exists('name', $item)) {
                    $name = $item['name'];
                    if ($name) {
                        $exists = in_array($name, $list);
                        $toExclude = in_array($name, $this->excludes);
                        if ($exists === false && $toExclude === false) {
                            $entry = [];
                            $entry['text'] = $name;
                            $entry['value'] = $name;
                            $list[] = $entry;
                        }
                    }
                }
            }
        }
        return $list;
    }

}