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

    public function onTwigSiteVariables()
    {

        if (!$this->isAdmin()) {
            $uuid = $this->grav['uri']->query('docuuid');
            $type = $this->grav['uri']->query('type');
            $testIDF = $this->grav['uri']->query('testIDF');

            $api = getenv('INGRID_API');

            if (empty($type)) {
                $type = "metadata";
            }

            $metadata_config = $this->grav['config']->get('plugins.ingrid-search-result.metadata');
            $address_config = $this->grav['config']->get('plugins.ingrid-search-result.address');

            $response = null;

            if ($testIDF) {
                $response = file_get_contents('user-data://test/detail/' . $type . '/idf/' . $testIDF);
            } else if ($uuid && $api) {
//                $response = Response::get($host);
                $client = new Client(['base_uri' => $api]);
                $responseContent = $client->request('POST', 'portal/search', [
                    'body' => $this->transformQuery($uuid)
                ])->getBody()->getContents();
                $response = json_decode($responseContent)->hits[0]->_source->idf;
            }

            if ($response) {
                $content = simplexml_load_string($response);
                $content->registerXPathNamespace('idf', 'http://www.portalu.de/IDF/1.0');
                $content->registerXPathNamespace('gco', 'http://www.isotc211.org/2005/gco');
                $content->registerXPathNamespace('gmd', 'http://www.isotc211.org/2005/gmd');
                $content->registerXPathNamespace('gml', 'http://www.opengis.net/gml/3.2');
                $content->registerXPathNamespace('gmx', 'http://www.isotc211.org/2005/gmx');
                $content->registerXPathNamespace('gts', 'http://www.isotc211.org/2005/gts');
                $content->registerXPathNamespace('srv', 'http://www.isotc211.org/2005/srv');
                $content->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');
                $content->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');

                if ($type == "address") {
                    $parser = new DetailAddressParser();
                    $hit = $parser->parse($content, $address_config);
                } else {
                    $parser = new DetailMetadataParser();
                    $hit = $parser->parse($content, $metadata_config);
                }
                $this->grav['twig']->twig_vars['detail_type'] = $type;
                $this->grav['twig']->twig_vars['hit'] = $hit;
            }
        }
    }

    public function onTwigExtensions()
    {
        require_once(__DIR__ . '/twig/DetailAddressTwigExtension.php');
        require_once(__DIR__ . '/twig/DetailMetadataTwigExtension.php');
        $this->grav['twig']->twig->addExtension(new DetailAddressTwigExtension());
        $this->grav['twig']->twig->addExtension(new DetailMetadataTwigExtension());
    }

    private function transformQuery(string $uuid): string
    {
        $query = array("query" => array("term" => array("t01_object.obj_id" => $uuid)));
        return json_encode($query);
    }

}
