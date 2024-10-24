<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;

/**
 * Class InGridCatalogPlugin
 * @package Grav\Plugin
 */
class InGridCatalogPlugin extends Plugin
{

    var string $config_api_url;
    var int $config_open_nodes_level;
    var int $config_display_partner;
    var int $config_open_on_new_tab;
    var int $config_sort_by_name;
    var array $excludes;

    var string $paramParentId;
    var string $paramIndex;
    var string $paramLevel;
    var string $paramNode;
    var string $paramPartner;
    var string $paramOpenNodes;

    var array $openNodes;

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

        $this->config_api_url = $config['api_url'];
        $this->config_open_nodes_level = $config['open_nodes_level'];
        $this->config_display_partner = $config['display_partner'];
        $this->config_open_on_new_tab = $config['open_on_new_tab'];
        $this->config_sort_by_name = $config['sort_by_name'];


        $route = $config['route'] ?? null;
        if ($route && $route == $uri->path()) {
            $this->paramParentId = $this->grav['uri']->query('parentId') ?: "";
            $this->paramIndex = $this->grav['uri']->query('index') ?: "";
            $this->paramLevel = $this->grav['uri']->query('level') ?: "";
            $this->paramPartner = $this->grav['uri']->query('partner') ?: "";
            $this->paramOpenNodes = $this->grav['uri']->query('openNodes') ?: "";
            $this->paramNode = $this->grav['uri']->query('node') ?: "";

            $this->openNodes = array();
            if ($this->paramParentId && $this->paramIndex) {
                // Parent loading
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplate', 0]
                ]);
            } else {
                // Initial loading
                $this->enable([
                    'onPageInitialized' => ['onPageInitialized', 0],
                    'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
                ]);
            }
        }
    }

    public function renderCustomTemplate(): void
    {
        $twig = $this->grav['twig'];
        // Use the @theme notation to reference the template in the theme
        $theme_path = $this->grav['twig']->addPath($this->grav['locator']->findResource('theme://templates'));
        $children = self::getCatalogChildren($this->paramIndex, $this->paramLevel, $this->paramPartner, $this->paramNode, $this->paramParentId);
        $detailPage = $this->grav['pages']->find('/detail');
        $catalogPage = $this->grav['pages']->find('/catalog');
        $rootUrl = $this->grav['uri']->rootUrl();
        $output = $twig->twig()->render($theme_path . '/partials/catalog/catalog-item.html.twig', [
            'items' => $children,
            'detailPage' => $detailPage ? $rootUrl . $detailPage->route() : '',
            'catalogPage' => $catalogPage ? $rootUrl . $catalogPage->route() : '',
        ]);
        echo $output;
        exit();
    }

    public function onPageInitialized(): void
    {
        echo '<script>console.log("Catalog");</script>';
    }

    public function onTwigSiteVariables(): void
    {

        if (!$this->isAdmin()) {
            $response = file_get_contents($this->config_api_url);
            $items = json_decode($response, true);
            $partners = self::getPartners($items);
            $this->grav['twig']->twig_vars['partners'] = $partners;
            $this->grav['twig']->twig_vars['api_url'] = $this->config_api_url;
            $this->grav['twig']->twig_vars['openNodesLevel'] = $this->config_open_nodes_level;
            $this->grav['twig']->twig_vars['displayPartner'] = $this->config_display_partner;
            $this->grav['twig']->twig_vars['openOnNewTab'] = $this->config_open_on_new_tab;
            $this->grav['twig']->twig_vars['openNodes'] = $this->openNodes;
        }
    }

    private function checkIsOpen(string $item, int $level): bool
    {
        $isOpen = false;
        $openNodesList = $this->paramOpenNodes != '' ? explode(',', $this->paramOpenNodes) : [];
        if (count($openNodesList) == 0) {
            if ($level <= $this->config_open_nodes_level) {
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
        $openNodesList = $this->paramOpenNodes != '' ? explode(',', $this->paramOpenNodes) : [];
        $exists = in_array($item, $openNodesList, true);
        if (!$exists) {
            $this->openNodes[] = $item;
        }
    }

    private function getPartners($items): array
    {
        $list = array();
        $partners = CodelistHelper::getCodelistPartners();
        foreach ($partners as $partner) {
            $partnerLevel = 1;
            $partnerId = $partner['ident'];
            $isPartnerOpen = self::checkIsOpen($partnerId, $partnerLevel);
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
                        $typeNode = self::getTypeNode($item['isAddress'], $item['id'], $partnerId, $catalogId);
                        $isOpen =  $providerLevel <= $this->config_open_nodes_level;
                        if ($isOpen) {
                            $this->openNodes[] = $catalogId;
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
            if ($this->config_sort_by_name) {
                usort($providerList, array($this, 'compare_name'));
            }
            $newPartner['children'] = $providerList;
            if (!empty($providerList) && $isPartnerOpen) {
                self::addToList($partnerId);
            }
            $list[] = $newPartner;
        }
        if ($this->config_sort_by_name) {
            usort($list, array($this, 'compare_name'));
        }
        return $list;
    }

    private function getTypeNode($isAddress, $id, $partner, $catalogId): array
    {
        $typeLevel  = 3;
        $typeId = $partner . '-' . substr(md5($catalogId . '-' . ($isAddress ? 'address' : 'object')), 0, 8);
        $isOpen = self::checkIsOpen($typeId, $typeLevel);
        if ($isOpen) {
            self::addToList($typeId);
        }
        $children = self::getCatalogChildren($id, $this->config_open_nodes_level, $partner, $catalogId . '-' . $typeId);
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

    private function getCatalogChildren($id, int $level, $partner, $catalogId, $parentId = null): array
    {
        $list = array();
        $catalog_api = $this->config_api_url . '/' . $id . '/hierarchy';
        if ($parentId) {
            $catalog_api = $catalog_api . '?parent=' . $parentId;
        }
        $response = file_get_contents($catalog_api);
        $items = json_decode($response, true);
        $catalogLevel = $level + 1;
        foreach ($items as $index => $item) {
            $catalogId = $partner . '-' . substr(md5($catalogId . '-' . $item['uuid']), 0, 8);
            $hasChildren = $item['hasChildren'];
            $isOpen = false;
            if ($hasChildren) {
                $isOpen = self::checkIsOpen($catalogId, $catalogLevel);
                if ($isOpen) {
                    self::addToList($catalogId);
                }
            }

            $node = [
                'name' => $item['name'],
                'level' => $catalogLevel,
                'uuid' => $item['uuid'],
                'docType' => $item['docType'],
                'isOpen' => $isOpen,
                'hasChildren' => $item['hasChildren'],
                'ident' => $id,
                'isAddress' => $item['isAddress'],
                'partner' => $partner,
                'id' => $catalogId
            ];
            $list[] = $node;
        }
        if ($this->config_sort_by_name) {
            usort($list, array($this, 'compare_name'));
        }
        return $list;
    }

    private function compare_name($a, $b): int
    {
        return strcasecmp($a['name'], $b['name']);
    }
}
