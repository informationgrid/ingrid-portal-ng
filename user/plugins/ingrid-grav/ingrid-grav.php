<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;

/**
 * Class InGridGravPlugin
 * @package Grav\Plugin
 */
class InGridGravPlugin extends Plugin
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
            'onTwigTemplatePaths'      => ['onTwigTemplatePaths', 0],
            'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', 0],
            'onPagesInitialized'       => ['onPagesInitialized', 0],
            'onTwigLoader'             => ['onTwigLoader', 0],
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
        if ($this->isAdmin()) {
            $this->enable([
                'onTwigSiteVariables' => ['onTwigAdminVariablesSelectizeDatasource', 0]
            ]);
            return;
        }

        $config = $this->config();
        $uri = $this->grav['uri'];
        $uri_path = $uri->path();
        switch ($uri_path) {
            case '/utils/mimetype':
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateMimetype', 0],
                ]);
                break;
            case '/utils/getUrlFileSize':
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateUrlFileSize', 0],
                ]);
                break;

            case '/datenquellen':
                $this->enable([
                    'onTwigSiteVariables' => ['onTwigSiteVariablesDatasource', 0]
                ]);
                break;

            case '/hilfe':
                $this->enable([
                    'onTwigSiteVariables' => ['onTwigSiteVariablesHelp', 0]
                ]);
                break;

            default:
                $this->enable([
                    'onPageInitialized' => ['onPageInitialized', 0],
                ]);
                break;
        }
    }

    public function onTwigTemplatePaths(): void
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * Add the Twig template paths to the Twig loader
     */
    public function onTwigLoader(): void
    {
        $this->grav['twig']->addPath(__DIR__ . '/templates');
    }

    public function onAdminTwigTemplatePaths($event) {
        $event['paths'] = array_merge($event['paths'], [__DIR__ . '/templates']);
        return $event;
    }

    public function onPageInitialized(): void
    {
        // Check themes config for redirected pages

        $uri = $this->grav['uri'];
        $uri_path = $uri->path();
        $page = $this->grav['pages']->find($uri_path);
        if ($page) {
            $theme = $this->grav['config']->get('system.pages.theme');
            $pages_to_404 = $this->grav['config']->get('themes.' . $theme . '.system.pages_to_404');
            $pages_to_redirect = $this->grav['config']->get('themes.' . $theme . '.system.pages_to_redirect');
            if (!empty($pages_to_404)) {
                if (in_array($page->rawRoute(), $pages_to_404)) {
                    $this->grav->redirect('/error');
                }
            }
        }
    }

    /**
     * Programmatically add a custom page.
     *
     * @param $url
     * @param $filename
     * @param null $parent
     * @param null $route
     * @return Page
     * @throws \Exception
     */
    public function addPage($url, $filename, $parent = null, $route = null): Page
    {
        /** @var Pages $pages */
        $pages = $this->grav['pages'];
        $page = $pages->dispatch($url);

        if (!$page) {
            $page = new Page;
            $pluginPage = new \SplFileInfo(__DIR__ . '/pages/' . $filename);
            if (!empty($pluginPage)) {
                $page->init(new \SplFileInfo(__DIR__ . '/pages/' . $filename));

                $route = $page->route();
                $page->rawRoute($url);
                $page->routeAliases([$url]);
                if ($parent) {
                    $page->parent($parent);
                } else {
                    $page->parent($pages->root());
                }
                $pages->addPage($page, $url);
                $pages->addPage($page, $route);
            }
        }
        return $page;
    }

    public function addPageFromTheme($url, $filename, $parent = null, $route = null): Page
    {
        /** @var Pages $pages */
        $pages = $this->grav['pages'];
        $page = $pages->dispatch($url);

        if (!$page) {
            $page = new Page;
            $themeFile = new \SplFileInfo($this->grav['locator']->findResource('theme://pages') . '/' . $filename);
            if (!empty($themeFile)) {
                $page->init($themeFile);

                $route = $page->route();
                $page->rawRoute($url);
                $page->routeAliases([$url]);
                if ($parent) {
                    $page->parent($parent);
                } else {
                    $page->parent($pages->root());
                }
                $pages->addPage($page, $url);
                $pages->addPage($page, $route);
            }
        }
        return $page;
    }

    /**
     * [onPagesInitialized]
     *
     * @return void
     * @throws \Exception
     */
    public function onPagesInitialized(): void
    {
        if ($this->isAdmin()) {
            $this->grav['admin']->enablePages();
        }

        /** @var Pages $pages */
        $pages = $this->grav['pages'];
        $this->addPage('/catalog', 'catalog/catalog.md');
        $this->addPage('/map', 'map/map.md');
        $this->addPage('/datasource', 'datasource/datasource.md');
        $this->addPage('/detail', 'detail/detail.md');
        $this->addPage('/help', 'help/help.md');
        $this->addPage('/measure', 'measure/measure.md');
        $this->addPage('/provider', 'provider/provider.md');
        $this->addPage('/rss', 'rss/news.md');
        $page = $this->addPage('/search', 'search/modular.md');
        $this->addPage('/search/_result', 'search/_result/result.md', $page);
        $this->addPage('/search/_search', 'search/_search/home-search.md', $page);
        $this->addPage('/sitemap', 'sitemap/sitemap.md');
        $this->addPage('/home/_categories', 'home/_categories/home-categories.md', $pages->find('/home'), '/home/_categories');
        $this->addPage('/home/_news', 'home/_news/home-news.md', $pages->find('/'), '/home/_news');
        $this->addPage('/home/_search', 'home/_search/home-search.md', $pages->find('/'), '/home/_search');
        $this->addPage('/home/_hits', 'home/_hits/home-hits.md', $pages->find('/'), '/home/_hits');
        $this->addPage('/contact/success', 'contact/success/contact-success.md', $pages->find('/contact'));
        $this->addPageFromTheme('/contact/form', 'contact/form/default.md', $pages->find('/contact'));
    }


    /*
     * Help
     */

    public function onTwigSiteVariablesHelp(): void
    {
        if (!$this->isAdmin()) {

            $uri = $this->grav['uri'];
            $config = $this->config();
            $hkey = $this->grav['uri']->query('hkey');
            if (!$hkey) {
                $hkey = $config['help']['default_hkey'];
            }
            $lang = $this->grav['language']->getActive() ?? $this->grav['language']->getDefault();

            if ($hkey) {
                libxml_use_internal_errors(true);

                // Content
                $theme = $this->grav['theme']->name;
                $xmlContent = new \DOMDocument();
                $xmlContent->load('theme://config/help/ingrid-portal-help_' . $lang . '.xml');
                libxml_clear_errors();

                // Help side menu xsl
                $procMenu = new \XSLTProcessor;
                $xslMenu = new \DOMDocument();
                $xslMenu->load('theme://config/help/ingrid-portal-help-menu.xsl');
                $procMenu->importStylesheet($xslMenu);
                $helpMenu = $procMenu->transformToXML($xmlContent);

                // Help xsl
                $procContent = new \XSLTProcessor;
                $xslContent = new \DOMDocument();
                $xslContent->load('theme://config/help/ingrid-portal-help.xsl');
                $procContent->importStylesheet($xslContent);

                $xpath = simplexml_load_string($xmlContent->saveXML());
                if ($xpath) {
                    $xmlQueryContent = $xpath->xpath('//section[@help-key="' . $hkey . '"]/ancestor::chapter');
                    if ($xmlQueryContent) {
                        $dom = new \DOMDocument;
                        $dom->loadXML($xmlQueryContent[0]->asXML());
                        $xmlContent = $dom;
                    }
                }
            }

            $helpContent = $procContent->transformToXML($xmlContent);
            $this->grav['twig']->twig_vars['help_content'] = str_replace('<?xml version="1.0"?>', '', $helpContent);
            $this->grav['twig']->twig_vars['help_menu'] = str_replace('<?xml version="1.0"?>', '', $helpMenu);
        }
    }

    /*
     * Datasource
     */

    public function onTwigAdminVariablesSelectizeDatasource(): void
    {
        if ($this->isAdmin()) {
            try {
                $config = $this->config();
                $api_url = $config['ingrid_api_url'] . '/portal/catalogs';
                $response = file_get_contents($api_url);
                $items = json_decode($response, true);
                $fieldSelectize = self::getAdminSelectizeDatasource($items);
                $this->grav['twig']->twig_vars['datasources'] = $fieldSelectize;
            } catch (\Exception $e) {
                $this->grav['log']->error($e->getMessage());
            }
        }
    }

    public function onTwigSiteVariablesDatasource(): void
    {
        if (!$this->isAdmin()) {
            $config = $this->config();
            $api_url = $config['ingrid_api_url'] . '/portal/catalogs';
            $excludes = $config['datasource']['excludes'] ?: [];
            $response = file_get_contents($api_url);
            $items = json_decode($response, true);
            $plugs = self::getDataSources($items, $excludes);
            $this->grav['twig']->twig_vars['plugs'] = $plugs;
        }
    }

    private function getDataSources($items, $excludes = []): array
    {
        $list = array();
        foreach ($items as $item) {
            if (array_key_exists('name', $item)) {
                $name = $item['name'];
                if ($name) {
                    $exists = in_array($name, $list);
                    $toExclude = in_array($name, $excludes);
                    if ($exists === false && $toExclude === false) {
                        $list[] = $name;
                    }
                }
            }
        }
        return $list;
    }

    private function getAdminSelectizeDatasource($items): array
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
                        $list[] = $entry;
                    }
                }
            }
        }
        return $list;
    }

    /*
     * URL file size
     */

    public function renderCustomTemplateUrlFileSize(): void
    {
        try {
            $paramUrl = $this->grav['uri']->query('url') ?: "";
            $headers = get_headers($paramUrl, true);
            if (substr($headers[0], 9, 3) == 200) {
                $contentLength = $headers['Content-Length'];
                echo StringHelper::formatBytes($contentLength);
            }
        } catch (\Exception $e) {
            $this->grav['log']->debug($e->getMessage());
        }
        exit();
    }

    /*
     * Mime type
     */

    public function renderCustomTemplateMimetype(): void
    {
        $twig = $this->grav['twig'];
        // Use the @theme notation to reference the template in the theme
        $theme_path = $twig->addPath($this->grav['locator']->findResource('theme://templates'));
        try {
            $paramUrl = $this->grav['uri']->query('url') ?: "";
            $mimeType = MimeTypeHelper::getUrlMimetype($paramUrl);
            $output = $twig->twig()->render($theme_path . '/_rest/utils/mimetype.html.twig', [
                'mimeType' => $mimeType
            ]);
            echo $output;
        } catch (\Exception $e) {
            $this->grav['log']->debug($e->getMessage());
        }
        exit();
    }
}
