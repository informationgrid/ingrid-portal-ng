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

    var string $configApiUrl;
    var string $configApiUrlCatalog;

    // Catalog
    var int $configCatalogOpenNodesLevel;
    var bool $configCatalogDisplayPartner;
    var bool $configCatalogOpenOnNewTab;
    var bool $configCatalogSortByName;
    var string $paramCatalogOpenNodes;
    var array $openCatalogNodes;

    var string $lang;

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

        $config = $this->config();
        $this->configApiUrl = $config['ingrid_api_url'];
        $this->configApiUrlCatalog = $this->configApiUrl . '/portal/catalogs';
        $this->lang = $this->grav['language']->getLanguage();

        if ($this->isAdmin()) {
            $this->enable([
                'onTwigSiteVariables' => ['onTwigAdminVariablesSelectizeDatasource', 0]
            ]);
            return;
        }

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

            case '/datenkataloge':
                $paramParentId = $uri->query('parentId') || "";
                $paramIndex = $uri->query('index') || "";

                $config = $this->config();
                $this->configCatalogOpenNodesLevel = $config['catalog']['open_nodes_level'];
                $this->configCatalogDisplayPartner = $config['catalog']['display_partner'];
                $this->configCatalogOpenOnNewTab = $config['catalog']['open_on_new_tab'];
                $this->configCatalogSortByName = $config['catalog']['sort_by_name'];

                $this->paramCatalogOpenNodes = $this->grav['uri']->query('openNodes') ?: "";
                $this->openCatalogNodes = [];

                if ($paramParentId && $paramIndex) {
                    // Parent loading
                    $this->enable([
                        'onPageInitialized' => ['renderCustomTemplateCatalog', 0]
                    ]);
                } else {
                    // Initial loading
                    $this->enable([
                        'onTwigSiteVariables' => ['onTwigSiteVariablesCatalog', 0]
                    ]);
                }
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
                $response = file_get_contents($this->configApiUrlCatalog);
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
            $excludes = $config['datasource']['excludes'] ?: [];
            $response = file_get_contents($this->configApiUrlCatalog);
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
                    $exists = in_array($name, $list);
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

    /*
     * Catalog
     */

    public function onTwigSiteVariablesCatalog(): void
    {

        if (!$this->isAdmin()) {
            $response = file_get_contents($this->configApiUrlCatalog);

            $items = json_decode($response, true);
            $partners = self::getPartners($items);
            $this->grav['twig']->twig_vars['partners'] = $partners;
            $this->grav['twig']->twig_vars['api_url'] = $this->configApiUrlCatalog;
            $this->grav['twig']->twig_vars['openNodesLevel'] = $this->configCatalogOpenNodesLevel;
            $this->grav['twig']->twig_vars['displayPartner'] = $this->configCatalogDisplayPartner;
            $this->grav['twig']->twig_vars['openOnNewTab'] = $this->configCatalogOpenOnNewTab;
            $this->grav['twig']->twig_vars['openNodes'] = $this->openCatalogNodes;
        }
    }

    public function renderCustomTemplateCatalog(): void
    {
        $twig = $this->grav['twig'];
        $uri = $this->grav['uri'];

        $paramParentId = $uri->query('parentId') ?: "";
        $paramIndex = $uri->query('index') ?: "";
        $paramLevel = $uri->query('level') ?: "";
        $paramPartner = $uri->query('partner') ?: "";
        $paramNode = $uri->query('node') ?: "";

        // Use the @theme notation to reference the template in the theme
        $theme_path = $this->grav['twig']->addPath($this->grav['locator']->findResource('theme://templates'));
        $children = self::getCatalogChildren($paramIndex, $paramLevel, $paramPartner, $paramNode, $paramParentId);
        $detailPage = $this->grav['pages']->find('/detail');
        $catalogPage = $this->grav['pages']->find('/catalog');
        $output = $twig->twig()->render($theme_path . '/partials/catalog/catalog-item.html.twig', [
            'items' => $children,
            'detailPage' => $detailPage ? $uri->rootUrl() . $detailPage->route() : '',
            'catalogPage' => $catalogPage ? $uri->rootUrl() . $catalogPage->route() : '',
        ]);
        echo $output;
        exit();
    }

    private function checkIsCatalogNodeOpen(string $item, int $level): bool
    {
        $isOpen = false;
        $openNodesList = $this->paramCatalogOpenNodes != '' ? explode(',', $this->paramCatalogOpenNodes) : [];
        if (count($openNodesList) == 0) {
            if ($level <= $this->configCatalogOpenNodesLevel) {
                $isOpen = true;
            }
        } else {
            $exists = in_array($item, $openNodesList, true);
            if ($exists) {
                $isOpen = true;
            }
        }
        return $isOpen;
    }

    private function addToList(string $item): void
    {
        $openNodesList = $this->paramCatalogOpenNodes != '' ? explode(',', $this->paramCatalogOpenNodes) : [];
        $exists = in_array($item, $openNodesList, true);
        if (!$exists) {
            $this->openCatalogNodes[] = $item;
        }
    }

    private function getPartners(array $items): array
    {
        $list = array();
        $partners = CodelistHelper::getCodelistPartners();
        foreach ($partners as $partner) {
            $partnerLevel = 1;
            $partnerId = $partner['ident'];
            $isPartnerOpen = self::checkIsCatalogNodeOpen($partnerId, $partnerLevel);
            $newPartner = [
                'name' => $partner['name'],
                'ident' => $partner['ident'],
                'level' => $partnerLevel,
                'isOpen' => $isPartnerOpen,
                'id' => $partnerId
            ];
            $providerList = array();
            foreach ($items as $item) {
                $catalogId = $partnerId . '-' . substr(md5($item['name']), 0, 8);
                $providerLevel = 2;
                $exists = in_array($newPartner['ident'], $item['partner']);
                if ($exists !== false && $item['isMetadata']) {
                    $providerExists = array_search($item['name'], array_column($providerList, 'name'));
                    if ($providerExists !== false) {
                        $provider = $providerList[$providerExists];
                        $typeNode = self::getTypeNode($item['isAddress'], $item['id'], $partnerId, $catalogId);
                        if ($typeNode) {
                            $provider['children'][] = $typeNode;
                            array_splice($providerList, $providerExists, 1);
                            $providerList[] = $provider;
                        }
                    } else {
                        $typeNode = self::getTypeNode((bool) $item['isAddress'], $item['id'], $partnerId, $catalogId);
                        $isOpen =  $providerLevel <= $this->configCatalogOpenNodesLevel;
                        if ($isOpen) {
                            $this->openCatalogNodes[] = $catalogId;
                        }
                        $provider = [
                            'name' => $item['name'],
                            'level' => $providerLevel,
                            'isOpen' => $isOpen,
                            'children' => $typeNode ? [$typeNode] : [],
                            'hasChildren' => (bool)$typeNode,
                            'partner' => $partnerId,
                            'id' => $catalogId
                        ];
                        $providerList[] = $provider;
                    }
                }
            }
            if ($this->configCatalogSortByName) {
                usort($providerList, array($this, 'compare_name'));
            }
            $newPartner['children'] = $providerList;
            if (!empty($providerList) && $isPartnerOpen) {
                self::addToList($partnerId);
            }
            $list[] = $newPartner;
        }
        if ($this->configCatalogSortByName) {
            usort($list, array($this, 'compare_name'));
        }
        return $list;
    }

    private function getTypeNode(bool $isAddress, string $id, string $partner, string $catalogId): array
    {
        $typeLevel  = 3;
        $typeId = $partner . '-' . substr(md5($catalogId . '-' . ($isAddress ? 'address' : 'object')), 0, 8);
        $isOpen = self::checkIsCatalogNodeOpen($typeId, $typeLevel);
        if ($isOpen) {
            self::addToList($typeId);
        }
        $children = self::getCatalogChildren($id, $this->configCatalogOpenNodesLevel, $partner, $catalogId . '-' . $typeId);
        $name = $isAddress ? 'CATALOG_HIERARCHY.TREE_ADDRESSES' : 'CATALOG_HIERARCHY.TREE_OBJECTS';
        return [
            'name' => $name,
            'level' => $typeLevel,
            'isOpen' => $isOpen,
            'children' => $children,
            'hasChildren' => count($children) > 0,
            'partner' => $partner,
            'id' => $typeId
        ];
    }

    private function getCatalogChildren(string $id, int $level, string $partner, string $catalogId, null|string $parentId = null): array
    {
        $list = [];
        $catalog_api = $this->configApiUrlCatalog . '/' . $id . '/hierarchy';
        if ($parentId) {
            $catalog_api = $catalog_api . '?parent=' . $parentId;
        }
        $response = file_get_contents($catalog_api);
        $items = json_decode($response, true);
        $catalogLevel = $level + 1;
        $freeAddresses = [];
        foreach ($items as $item) {
            $catalogId = $partner . '-' . substr(md5($catalogId . '-' . $item['uuid']), 0, 8);
            $isAddress = $item['isAddress'];
            $type = $item['docType'];
            $isOpen = false;
            $hasChildren = $item['hasChildren'];
            if ($hasChildren) {
                $isOpen = self::checkIsCatalogNodeOpen($catalogId, $catalogLevel);
                if ($isOpen) {
                    self::addToList($catalogId);
                }
            }
            $name = trim($isAddress ? implode(' ', array_reverse(explode(', ', $item['name']))) : $item['name']);
            $name = explode('#locale-', $name)[0];
            $node = [
                'name' => $name,
                'level' => $catalogLevel,
                'uuid' => $item['uuid'],
                'type' => $type,
                'type_name' => $isAddress ? $type : CodelistHelper::getCodelistEntry(["8000"], $type, $this->lang),
                'isOpen' => $isOpen,
                'hasChildren' => $item['hasChildren'],
                'ident' => $id,
                'isAddress' => $isAddress,
                'partner' => $partner,
                'id' => $catalogId
            ];
            if ($parentId == null && $isAddress && $type == '3') {
                $freeAddresses[] = $node;
            } else {
                $list[] = $node;
            }
        }
        if ($this->configCatalogSortByName) {
            usort($list, array($this, 'compare_name'));
        }
        if (!empty($freeAddresses)) {
            $freeAddressId = $partner . '-' . substr(md5('CATALOG_HIERARCHY.TREE_ADDRESSES_FREE'), 0, 8);
            $isOpen = self::checkIsCatalogNodeOpen($freeAddressId, $level + 1);
            if ($isOpen) {
                self::addToList($freeAddressId);
            }
            usort($freeAddresses, array($this, 'compare_name'));
            array_unshift($list , [
                'name' => 'CATALOG_HIERARCHY.TREE_ADDRESSES_FREE',
                'level' => $level + 1,
                'type' => '1000',
                'isOpen' => $isOpen,
                'children' => $freeAddresses,
                'ident' => $freeAddressId,
                'hasChildren' => count($freeAddresses) > 0,
                'partner' => $partner,
                'id' => $freeAddressId
            ]);
        }
        return $list;
    }

    private function compare_name(array $a, array $b): int
    {
        return strcasecmp($a['name'], $b['name']);
    }
}
