<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\GPM\Response;
use Grav\Common\Plugin;
use Grav\Common\Twig\Twig;
use GuzzleHttp\Client;

/**
 * Class InGridDetailPlugin
 * @package Grav\Plugin
 */
class InGridDetailPlugin extends Plugin
{
    var $log;
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

        $this->log = $this->grav['log'];
        $uri = $this->grav['uri'];
        $uri_path = $uri->path();
        $config = $this->config();
        $routes = $config['routes'] ?? null;
        if ($routes && in_array($uri_path, $routes)) {
            // Detail request
            if ($uri_path == "/trefferanzeige") {
                $this->enable([
                    'onPageInitialized' => ['onPageInitialized', 0],
                    'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
                    'onTwigExtensions' => ['onTwigExtensions', 0],
                ]);
            }

            // Create zip request
            if ($uri_path == "/detail/createZip") {
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateDetailCreateZip', 0],
                ]);
            }
            // Get zip request
            if ($uri_path == "/detail/getZip") {
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateDetailGetZip', 0],
                ]);
            }
        }
    }

    public function onPageInitialized(): void
    {
        echo "<script>console.log('InGrid Detail');</script>";
    }

    public function renderCustomTemplateDetailCreateZip(): void
    {
        $twig = $this->grav['twig'];
        // Use the @theme notation to reference the template in the theme
        $theme_path = $twig->addPath($this->grav['locator']->findResource('theme://templates'));
        try {
            $api = $this->grav['config']->get('plugins.ingrid-detail.ingrid_api_url');
            $paramUuid = $this->grav['uri']->query('uuid') ?: '';
            $paramType = $this->grav['uri']->query('type') ?: 'metadata';
            $responseContent = self::getResponseContent($api, $paramUuid, $paramType);
            $hits = json_decode($responseContent)->hits;
            $response = null;
            $plugId = null;
            $title = null;
            if (count($hits) > 0) {
                $response = $hits[0]->_source->idf;
                $plugId = $hits[0]->_source->iPlugId;
                $title = $hits[0]->_source->title;
            }
            $output = '';
            if (!empty($response)) {
                $parser = new DetailCreateZipUVPServiceImpl('downloads/zip', $title, $paramUuid, $plugId, $this->grav);
                $content = simplexml_load_string($response);
                IdfHelper::registerNamespaces($content);
                [$fileUrl, $fileSize] = $parser->parse($content);
                $output = $twig->twig()->render($theme_path . '/_rest/detail/createZip.html.twig', [
                    'fileUrl' => $fileUrl,
                    'fileSize' => $fileSize,
                ]);
            }
            echo $output;
        } catch (\Exception $e) {
            $this->grav['log']->debug($e->getMessage());
        }
        exit();
    }

    public function renderCustomTemplateDetailGetZip(): void
    {
        try {
            $paramUuid = $this->grav['uri']->query('uuid');
            $paramPlugId = $this->grav['uri']->query('plugid');
            $locator = $this->grav['locator'];
            $folderPath = $locator->findResource('user-data://', true);
            $dir = $folderPath . '/downloads/zip/' . $paramPlugId . '/' . $paramUuid;
            $dirFiles = scandir($dir);
            $filename = '';
            foreach ($dirFiles as $dirFile) {
                if (str_ends_with($dirFile, '.zip')) {
                    $filename = $dirFile;
                }
            }
            $file = file($dir . '/' . $filename);
            if ($file) {
                header('Content-Type: application/zip');
                header('Content-Length: ' . filesize($dir . '/' . $filename));
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                readfile($dir . '/' . $filename);
            }
        } catch (\Exception $e) {
            $this->grav['log']->debug($e->getMessage());
        }
        exit();
    }
    public function onTwigSiteVariables(): void
    {

        if (!$this->isAdmin()) {
            $uuid = $this->grav['uri']->query('docuuid');
            $type = $this->grav['uri']->query('type');
            $testIDF = $this->grav['uri']->query('testIDF');
            $cswUrl = $this->grav['uri']->query('cswUrl');
            $lang = $this->grav['language']->getLanguage();
            $theme = $this->grav['config']->get('system.pages.theme');
            $timezone = $this->grav['config']->get('system.timezone');

            $api = $this->grav['config']->get('plugins.ingrid-detail.ingrid_api_url');

            if (empty($type)) {
                $type = "metadata";
            }

            $response = null;
            $dataSourceName = null;
            $partners = null;
            $providers = null;

            try {
                if ($testIDF) {
                    $response = file_get_contents('user-data://test/detail/' . $type . '/idf/' . $testIDF);
                } else if ($cswUrl) {
                    $response = file_get_contents($cswUrl);
                } else if ($uuid && $api) {
                    $responseContent = self::getResponseContent($api, $uuid, $type);
                    $hits = json_decode($responseContent)->hits;
                    if(count($hits) > 0) {
                        $response = $hits[0]->_source->idf;
                        $dataSourceName = $hits[0]->_source->dataSourceName;
                        $partners = $hits[0]->_source->partner;
                    }
                }

                if ($response) {
                    $content = simplexml_load_string($response);
                    IdfHelper::registerNamespaces($content);

                    if ($type == "address") {
                        $parser = new DetailAddress($theme);
                        $hit = $parser->parse($content, $uuid, $this->grav);
                    } else {
                        $parser = new DetailMetadata($theme);
                        $hit = $parser->parse($content, $uuid, $dataSourceName, $providers, $this->grav);
                    }
                    $this->grav['twig']->twig_vars['detail_type'] = $type;
                    $this->grav['twig']->twig_vars['hit'] = $hit;
                    $this->grav['twig']->twig_vars['page_custom_title'] = $hit["title"] ?? null;
                    $this->grav['twig']->twig_vars['partners'] = $partners;
                    $this->grav['twig']->twig_vars['lang'] = $lang;
                    $this->grav['twig']->twig_vars['paramsMore'] = explode(",", $this->grav['uri']->query('more'));
                    $this->grav['twig']->twig_vars['timezone'] = !empty($timezone) ? $timezone : 'Europe/Berlin';
                }
            } catch (\Exception $e){
                $this->log->error("Error open detail: " . $e);
                $this->grav['twig']->twig_vars['hit'] = [];
            }
        }
    }

    public function onTwigExtensions(): void
    {
        require_once(__DIR__ . '/twig/DetailTwigExtension.php');
        $this->grav['twig']->twig->addExtension(new DetailTwigExtension());
    }

    private function getResponseContent(string $api, string $uuid, string $type): string
    {
        $client = new Client(['base_uri' => $api]);
        return $client->request('POST', 'portal/search', [
            'body' => $this->transformQuery($uuid, $type)
        ])->getBody()->getContents();
    }

    private function transformQuery(string $uuid, string $type): string
    {
        $indexField = "t01_object.obj_id";
        if($type == "address") {
            $indexField = "t02_address.adr_id";
        }
        $query = array("query" => array("query_string" => array("query" => $indexField . ":\"" . $uuid . "\"")));
        return json_encode($query);
    }

}
