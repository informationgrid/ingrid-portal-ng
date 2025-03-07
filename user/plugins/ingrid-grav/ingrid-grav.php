<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class InGridGravPlugin
 * @package Grav\Plugin
 */
class InGridGravPlugin extends Plugin
{

    public string $configApiUrl;
    public string $configApiUrlCatalog;

    public string $lang;

    public SearchService $service;

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
            'onAdminMenu'              => ['onAdminMenu', 0],
            'onAdminTaskExecute'       => ['onAdminTaskExecute', 0],
            'onTwigTemplatePaths'      => ['onTwigTemplatePaths', 0],
            'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', 0],
            'onPagesInitialized'       => ['onPagesInitialized', 0],
            'onSchedulerInitialized'   => ['onSchedulerInitialized', 0],
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

        $config = $this->config();
        $this->configApiUrl = $config['ingrid_api']['url'];
        $this->configApiUrlCatalog = $this->configApiUrl . '/portal/catalogs';
        $this->lang = $this->grav['language']->getLanguage();

        if ($this->isAdmin()) {
            $this->enable([
                'onTwigSiteVariables' => ['onTwigAdminVariables', 0]
            ]);
            return;
        }

        $uri = $this->grav['uri'];
        $uri_path = $uri->path();

        $this->grav['log']->debug('Incoming request URL on ingrid-grav plugin: ' . $uri_path);
        // Get rest content
        switch ($uri_path) {
            case '/rest/getMimeType':
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateMimetype', 0],
                ]);
                break;
            case '/rest/getUrlFileSize':
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateUrlFileSize', 0],
                ]);
                break;

            case '/rest/createDetailZip':
                // Create zip request
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateDetailCreateZip', 0],
                ]);
                break;
            case '/rest/getDetailZip':
                // Get zip request
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateDetailGetZip', 0],
                ]);
                break;
            case '/rest/getMapMarkers':
                $this->enable([
                    'onTwigSiteVariables' => ['onTwigSiteVariablesMapMarkers', 0],
                ]);
                break;
            case '/rest/getCatalogLeaf':
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateCatalog', 0],
                ]);
                break;
            default:
                // Get page content
                $this->enable([
                    'onPageInitialized' => ['onPageInitialized', 0],
                    'onTwigExtensions' => ['onTwigExtensions', 0],
                ]);
                break;
        }
    }

    public function onTwigTemplatePaths(): void
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    public function onSchedulerInitialized(Event $e): void
    {
        $codelist = new Codelist($this->grav);
        $codelist->setScheduler($e);

        $rss = new Rss($this->grav);
        $rss->setScheduler($e);
    }

    /**
     * Add reindex button to the admin QuickTray
     */
    public function onAdminMenu(): void
    {
        $this->grav['twig']->plugins_quick_tray['InGrid Codelist'] = [
            'authorize' => 'taskReindexCodelist',
            'hint' => $this->grav['language']->translate(['PLUGIN_INGRID_GRAV.CODELIST_API.GRAV_MENU_TEXT']),
            'class' => 'codelist-reindex',
            'icon' => 'fa-book'
        ];
        $this->grav['twig']->plugins_quick_tray['InGrid RSS'] = [
            'authorize' => 'taskReindexRss',
            'hint' => $this->grav['language']->translate(['PLUGIN_INGRID_GRAV.RSS.GRAV_MENU_TEXT']),
            'class' => 'rss-reindex',
            'icon' => 'fa-rss'
        ];
    }

    /**
     * Handle the Reindex task from the admin
     *
     * @param Event $e
     */
    public function onAdminTaskExecute(Event $e): void
    {
        switch ($e['method']) {
            case 'taskReindexCodelist':
                $codelist = new Codelist($this->grav);
                $codelist->taskReindex($e);
                break;
            case 'taskReindexRss':
                $rss = new Rss($this->grav);
                $rss->taskReindex($e);
                break;
            default:
                break;
        }
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
            $pages_to_404 = $this->grav['config']->get('themes.' . $theme . '.redirect.pages_to_404');
            $pages_to_redirect = $this->grav['config']->get('themes.' . $theme . '.redirect.pages_to_redirect');
            if (!empty($pages_to_404)) {
                if (in_array($page->rawRoute(), $pages_to_404)) {
                    $this->grav->redirect('/error');
                }
            }
            if (!empty($pages_to_redirect)) {
                foreach ($pages_to_redirect as $redirect) {
                    $redirectPath = $redirect['path'];
                    $redirectUrl = $redirect['url'];
                    if (!empty($redirectPath) && !empty($redirectUrl)) {
                        if ($redirectPath == $page->rawRoute()) {
                            $this->grav->redirect($redirectUrl);
                        }
                    }
                }
            }
        }

        if ($page) {
            // Get pages content
            switch ($page->folder()) {

                case 'datasource':
                    $this->enable([
                        'onTwigSiteVariables' => ['onTwigSiteVariablesDatasource', 0]
                    ]);
                    break;

                case 'help':
                    $this->enable([
                        'onTwigSiteVariables' => ['onTwigSiteVariablesHelp', 0]
                    ]);
                    break;

                case 'catalog':
                    $this->enable([
                        'onTwigSiteVariables' => ['onTwigSiteVariablesCatalog', 0]
                    ]);
                    break;

                case 'detail':
                    // Detaildarstellung
                    $this->enable([
                        'onTwigSiteVariables' => ['onTwigSiteVariablesDetail', 0],
                    ]);
                    break;

                case 'home':
                    // Startseite
                    $this->enable([
                        'onTwigSiteVariables' => ['onTwigSiteVariablesHome', 0],
                    ]);
                    break;

                case 'search':
                    // Suche
                    $this->enable([
                        'onTwigSiteVariables' => ['onTwigSiteVariablesSearch', 0],
                    ]);
                    break;

                case 'provider':
                    // Informationsanbieter
                    $this->enable([
                        'onTwigSiteVariables' => ['onTwigSiteVariablesProvider', 0],
                    ]);
                    break;

                case 'map':
                    // UVP legend
                    $this->enable([
                        'onTwigSiteVariables' => ['onTwigSiteVariablesMapLegend', 0],
                    ]);
                    break;

                case 'rss':
                    // Startseite
                    $this->enable([
                        'onTwigSiteVariables' => ['onTwigSiteVariablesRss', 0],
                    ]);
                    break;

                default:
                    break;
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
    public function addPage(string $url, string $filename, ?Page $parent = null, ?string $route = null): Page
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

    public function addPageFromTheme(string $url, string $filename, ?Page $parent = null, ?string $route = null): Page
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
            $help = new Help($this->grav);
            $help->getContent();
            $this->grav['twig']->twig_vars['help_content'] = str_replace('<?xml version="1.0"?>', '', $help->helpContent ?: '');
            $this->grav['twig']->twig_vars['help_menu'] = str_replace('<?xml version="1.0"?>', '', $help->helpMenu ?: '');
        }
    }

    /*
     * Datasource
     */

    public function onTwigSiteVariablesDatasource(): void
    {
        if (!$this->isAdmin()) {
            $datasource = new Datasource($this->grav, $this->configApiUrlCatalog);
            $this->grav['twig']->twig_vars['plugs'] = $datasource->getContent();
        }
    }

    public function onTwigAdminVariables(): void
    {
        if ($this->isAdmin()) {
            $twig = $this->grav['twig'];
            try {
                $datasource = new Datasource($this->grav, $this->configApiUrlCatalog);
                $twig->twig_vars['datasources'] = $datasource->getAdminContent();

                $codelist = new Codelist($this->grav);
                [$status, $msg] = $codelist->getCount();
                $twig->twig_vars['codelist_index_status'] = ['status' => $status, 'msg' => $msg];
                $this->grav['assets']->addCss('plugin://ingrid-grav/assets/admin/codelist/codelist.css');
                $this->grav['assets']->addJs('plugin://ingrid-grav/assets/admin/codelist/codelist.js');

                $rss = new Rss($this->grav);
                [$status, $msg] = $rss->getCount();
                $twig->twig_vars['rss_index_status'] = ['status' => $status, 'msg' => $msg];
                $this->grav['assets']->addCss('plugin://ingrid-grav/assets/admin/rss/rss.css');
                $this->grav['assets']->addJs('plugin://ingrid-grav/assets/admin/rss/rss.js');
            } catch (\Exception $e) {
                $this->grav['log']->error($e->getMessage());
            }
        }
    }

    /*
     * REST: URL file size
     */

    public function renderCustomTemplateUrlFileSize(): void
    {
        try {
            $paramUrl = $this->grav['uri']->query('url') ?: "";
            $opts['http']['timeout'] = 3;
            $context = stream_context_create( $opts );
            $headers = get_headers($paramUrl, true, $context);
            if (substr($headers[0], 9, 3) == 200) {
                $contentLength = $headers['Content-Length'];
                echo StringHelper::formatBytes($contentLength);
            }
        } catch (\Exception $e) {
            $this->grav['log']->error('Error load file size: ' . $e->getMessage());
        }
        exit();
    }

    /*
     * REST: Mime type
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
            echo trim($output);
        } catch (\Exception $e) {
            $this->grav['log']->error('Error load mime type: ' . $e->getMessage());
        }
        exit();
    }

    /*
     * Catalog
     */

    public function onTwigSiteVariablesCatalog(): void
    {

        if (!$this->isAdmin()) {
            $catalog = new Catalog($this->grav, $this->configApiUrlCatalog);
            $items = $catalog->getContent();
            $this->grav['twig']->twig_vars['partners'] = $items;
            $this->grav['twig']->twig_vars['api_url'] = $catalog->configApi;
            $this->grav['twig']->twig_vars['openNodesLevel'] = $catalog->configCatalogOpenNodesLevel;
            $this->grav['twig']->twig_vars['displayPartner'] = $catalog->configCatalogDisplayPartner;
            $this->grav['twig']->twig_vars['openOnNewTab'] = $catalog->configCatalogOpenOnNewTab;
            $this->grav['twig']->twig_vars['openNodes'] = $catalog->openCatalogNodes;
        }
    }

    public function renderCustomTemplateCatalog(): void
    {
        try {
            $catalog = new Catalog($this->grav, $this->configApiUrlCatalog);
            echo $catalog->getContentLeaf();
        } catch (\Exception $e) {
            $this->grav['log']->error($e->getMessage());
        }
        exit();
    }

    /*
     * REST: ZIP
     */

    public function renderCustomTemplateDetailCreateZip(): void
    {
        try {
            $detail = new Detail($this->grav, $this->configApiUrl);
            echo $detail->getContentZipOutput();
        } catch (\Exception $e) {
            $this->grav['log']->error($e->getMessage());
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
            $this->grav['log']->error($e->getMessage());
        }
        exit();
    }

    /*
     * Detail
     */

    public function onTwigSiteVariablesDetail(): void
    {

        if (!$this->isAdmin()) {
            $detail = new Detail($this->grav, $this->configApiUrl);
            $detail->getContent();
            $twig = $this->grav['twig'];
            if (isset($detail->hit)) {
                $twig->twig_vars['detail_type'] = $detail->type;
                $twig->twig_vars['hit'] = $detail->hit;
                if ($detail->hit instanceof DetailMetadataHTML) {
                    $twig->twig_vars['page_custom_title'] = $detail->title ?? null;
                } else {
                    $twig->twig_vars['page_custom_title'] = $detail->hit->title ?? null;
                }
                $twig->twig_vars['partners'] = $detail->partners;
                $twig->twig_vars['lang'] = $detail->lang;
                $twig->twig_vars['paramsMore'] = explode(",", $this->grav['uri']->query('more'));
                $twig->twig_vars['timezone'] = !empty($detail->timezone) ? $detail->timezone : 'Europe/Berlin';
                $twig->twig_vars['csw_url'] = $this->config()['csw']['url'];
                $twig->twig_vars['rdf_url'] = $this->config()['rdf']['url'];
            } else {
                $twig->twig_vars['hit'] = [];
            }
        }
    }

    /*
     * Search
     */
    public function onTwigSiteVariablesSearch(): void
    {
        if (!$this->isAdmin()) {
            $search = new Search($this->grav, $this->configApiUrl);
            $search->getContent();
            $twig = $this->grav['twig'];
            $twig->twig_vars['query'] = $search->query;
            $twig->twig_vars['selected_facets'] = $search->selectedFacets;
            $twig->twig_vars['facetMapCenter'] = array(51.3, 10, 5);
            $twig->twig_vars['search_result'] = $search->results;
            $twig->twig_vars['hitsNum'] = $search->hitsNum;
            $twig->twig_vars['pagingUrl'] = $search->getPagingUrl($this->grav['uri']);
            $twig->twig_vars['search_ranking'] = $search->ranking;
            $twig->twig_vars['csw_url'] = $this->config()['csw']['url'];
            $twig->twig_vars['rdf_url'] = $this->config()['rdf']['url'];
            $twig->twig_vars['display_sort_hits'] = $search->isSortHitsEnable();

        }
    }

    public function onTwigSiteVariablesHome(): void
    {
        if (!$this->isAdmin()) {
            $twig = $this->grav['twig'];

            $categories = new CategoryFacet($this->grav);
            $twig->twig_vars['categories_result'] = $categories->getContent();

            $hitsOverview = new HitOverview($this->grav);
            $twig->twig_vars['hits_result'] = $hitsOverview->getContent();

            $rss = new Rss($this->grav);
            $twig->twig_vars['rss_feeds'] = $rss->getContent();
        }
    }

    public function onTwigSiteVariablesProvider(): void
    {

        if (!$this->isAdmin()) {
            $provider = new Provider($this->grav);
            $this->grav['twig']->twig_vars['partners'] = $provider->getContent();
        }
    }

    public function onTwigSiteVariablesRss(): void
    {
        if (!$this->isAdmin()) {
            $rss = new Rss($this->grav);
            $this->grav['twig']->twig_vars['rss_feeds'] = $rss->getContent();
        }
    }

    public function onTwigSiteVariablesMapLegend(): void
    {
        if (!$this->isAdmin()) {
            $search = new Search($this->grav, $this->configApiUrl);
            $search->getContentMapLegend();
            if ($search->results) {
                $this->grav['twig']->twig_vars['legend'] = json_encode($search->results->facets);
                $this->grav['twig']->twig_vars['requestLayer'] = $this->grav['uri']->query('layer') ?: "";
                $this->grav['twig']->twig_vars['mapParamE'] = $this->grav['uri']->query('E') ?: "";
                $this->grav['twig']->twig_vars['mapParamN'] = $this->grav['uri']->query('N') ?: "";
                $this->grav['twig']->twig_vars['mapParamZoom'] = $this->grav['uri']->query('zoom') ?: "";
                $this->grav['twig']->twig_vars['mapParamExtent'] = $this->grav['uri']->query('extent') ?: "";
            }
        }
    }

    public function onTwigSiteVariablesMapMarkers(): void
    {
        if (!$this->isAdmin()) {
            try {
                $search = new Search($this->grav, $this->configApiUrl);
                $output = $search->getContentMapMarkers();
                echo json_encode($output);
            } catch (\Exception $e) {
                $this->grav['log']->error($e->getMessage());
            }
        }
        exit;
    }

    public function onTwigExtensions(): void
    {
        require_once(__DIR__ . '/twig/InGridGravTwigExtension.php');
        $this->grav['twig']->twig->addExtension(new InGridGravTwigExtension());
    }

    /*
     * Used in theme on default blueprint:
     * "data-options@: '\Grav\Plugin\InGridGravPlugin::getAdminDetailContactOrderOptions'"
     */
    public static function getAdminDetailContactOrderOptions(): array
    {
        $grav = Grav::instance();
        return array(
            array(
                'text' => $grav['language']->translate('THEME.ADMIN.HIT_DETAIL.CONTACT_ORDER.OPTIONS.POINTOFCONTACT'),
                'value' => 'pointOfContact'
            ),
            array(
                'text' => $grav['language']->translate('THEME.ADMIN.HIT_DETAIL.CONTACT_ORDER.OPTIONS.DISTRIBUTOR'),
                'value' => 'distributor'
            ),
            array(
                'text' => $grav['language']->translate('THEME.ADMIN.HIT_DETAIL.CONTACT_ORDER.OPTIONS.PUBLISHER'),
                'value' => 'publisher'
            ),
            array(
                'text' => $grav['language']->translate('THEME.ADMIN.HIT_DETAIL.CONTACT_ORDER.OPTIONS.OWNER'),
                'value' => 'owner'
            ),
            array(
                'text' => $grav['language']->translate('THEME.ADMIN.HIT_DETAIL.CONTACT_ORDER.OPTIONS.PROCESSOR'),
                'value' => 'processor'
            ),
            array(
                'text' => $grav['language']->translate('THEME.ADMIN.HIT_DETAIL.CONTACT_ORDER.OPTIONS.AUTHOR'),
                'value' => 'author'
            )
        );
    }

    /*
     * Used in theme on default blueprint:
     * "data-options@: '\Grav\Plugin\InGridGravPlugin::getAdminDetailSortAddressOptions'"
     */
    public static function getAdminDetailSortAddressOptions(): array
    {
        $grav = Grav::instance();
        return array(
            array(
                'text' => $grav['language']->translate('THEME.ADMIN.HIT_DETAIL.ADDRESS_ORDER.OPTIONS.0'),
                'value' => '0'
            ),
            array(
                'text' => $grav['language']->translate('THEME.ADMIN.HIT_DETAIL.ADDRESS_ORDER.OPTIONS.1'),
                'value' => '1'
            ),
            array(
                'text' => $grav['language']->translate('THEME.ADMIN.HIT_DETAIL.ADDRESS_ORDER.OPTIONS.2'),
                'value' => '2'
            ),
            array(
                'text' => $grav['language']->translate('THEME.ADMIN.HIT_DETAIL.ADDRESS_ORDER.OPTIONS.3'),
                'value' => '3'
            )
        );
    }
}
