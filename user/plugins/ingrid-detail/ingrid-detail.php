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
        $config = $this->config();

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
        echo "<script>console.log('InGrid Detail');</script>";
    }

    public function onTwigSiteVariables(): void
    {

        if (!$this->isAdmin()) {
            $uuid = $this->grav['uri']->query('docuuid');
            $type = $this->grav['uri']->query('type');
            $testIDF = $this->grav['uri']->query('testIDF');
            $cswUrl = $this->grav['uri']->query('cswUrl');
            $rootUrl = $this->grav['uri']->rootUrl();
            $lang = $this->grav['language']->getLanguage();

            $api = getenv('INGRID_API') !== false ?
                getenv('INGRID_API') : $this->grav['config']->get('plugins.ingrid-detail.ingrid_api_url');

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
//                $response = Response::get($host);
                    $client = new Client(['base_uri' => $api]);
                    $responseContent = $client->request('POST', 'portal/search', [
                        'body' => $this->transformQuery($uuid, $type)
                    ])->getBody()->getContents();
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
                        $parser = new DetailAddressParser();
                        $hit = $parser->parse($content, $uuid, $this->grav);
                    } else {
                        $parser = new DetailMetadataParser();
                        $hit = $parser->parse($content, $uuid, $dataSourceName, $providers, $this->grav);
                    }
                    $this->grav['twig']->twig_vars['detail_type'] = $type;
                    $this->grav['twig']->twig_vars['hit'] = $hit;
                    $this->grav['twig']->twig_vars['page_custom_title'] = $hit["title"] ?? null;
                    $this->grav['twig']->twig_vars['partners'] = $partners;
                    $this->grav['twig']->twig_vars['lang'] = $lang;
                    $this->grav['twig']->twig_vars['paramsMore'] = explode(",", $this->grav['uri']->query('more'));
                    $this->grav['twig']->twig_vars['rootUrl'] = $rootUrl;
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
