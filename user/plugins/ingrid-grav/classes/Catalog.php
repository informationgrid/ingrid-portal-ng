<?php

namespace Grav\Plugin;

use Grav\Common\Grav;
use PHPUnit\Framework\Exception;

class Catalog
{
    public Grav $grav;
    public string $configApi;
    public int $configCatalogOpenNodesLevel;
    public bool $configCatalogDisplayPartner;
    public bool $configCatalogOpenOnNewTab;
    public bool $configCatalogSortByName;
    public string $paramCatalogOpenNodes;
    public array $openCatalogNodes;

    public string $lang;

    public function __construct(Grav $grav, string $api)
    {
        $this->grav = $grav;
        $this->configApi = $api;
        $this->lang = $grav['language']->getLanguage();

        $theme = $this->grav['config']->get('system.pages.theme');
        $this->configCatalogOpenNodesLevel = $this->grav['config']->get('themes.' . $theme . '.catalog.open_nodes_level');
        $this->configCatalogDisplayPartner = $this->grav['config']->get('themes.' . $theme . '.catalog.display_partner') ?: true;
        $this->configCatalogOpenOnNewTab = $this->grav['config']->get('themes.' . $theme . '.catalog.open_on_new_tab') ?: true;
        $this->configCatalogSortByName = $this->grav['config']->get('themes.' . $theme . '.catalog.sort_by_name') ?: true;

        $this->paramCatalogOpenNodes = $this->grav['uri']->query('openNodes') ?: "";
        $this->openCatalogNodes = [];
    }

    public function getContent(): array
    {
        $list = array();

        if (($response = @file_get_contents($this->configApi)) !== false) {
            $items = json_decode($response, true);
            $partners = CodelistHelper::getCodelistPartners();
            foreach ($partners as $partner) {
                $partnerLevel = 1;
                $partnerId = $partner['ident'];
                $isPartnerOpen = $this->checkIsCatalogNodeOpen($partnerId, $partnerLevel);
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
                            $typeNode = $this->getTypeNode($item['isAddress'], $item['id'], $partnerId, $catalogId);
                            if ($typeNode) {
                                $provider['children'][] = $typeNode;
                                array_splice($providerList, $providerExists, 1);
                                $providerList[] = $provider;
                            }
                        } else {
                            $typeNode = $this->getTypeNode((bool)$item['isAddress'], $item['id'], $partnerId, $catalogId);
                            $isOpen = $providerLevel <= $this->configCatalogOpenNodesLevel;
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
                    $this->addToList($partnerId);
                }
                $list[] = $newPartner;
            }
            if ($this->configCatalogSortByName) {
                usort($list, array($this, 'compare_name'));
            }
        }

        return $list;
    }

    public function getContentLeaf(): string
    {
        $twig = $this->grav['twig'];
        $uri = $this->grav['uri'];

        $paramParentId = $uri->query('parentId') ?: "";
        $paramIndex = $uri->query('index') ?: "";
        $paramLevel = $uri->query('level') ?: "";
        $paramPartner = $uri->query('partner') ?: "";
        $paramNode = $uri->query('node') ?: "";

        // Use the @theme notation to reference the template in the theme
        $theme_path = $twig->addPath($this->grav['locator']->findResource('theme://templates'));

        $children = $this->getCatalogChildren($paramIndex, $paramLevel, $paramPartner, $paramNode, $paramParentId);
        $detailPage = $this->grav['pages']->find('/detail');
        return $twig->twig()->render($theme_path . '/partials/catalog/catalog-item.html.twig', [
            'openOnNewTab' => $this->configCatalogOpenOnNewTab,
            'items' => $children,
            'detailPage' => $detailPage ? $uri->rootUrl() . $detailPage->route() : '',
            'catalogLeafRest' => 'rest/getCatalogLeaf',
        ]);
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

    private function getTypeNode(bool $isAddress, string $id, string $partner, string $catalogId): array
    {
        $typeLevel  = 3;
        $typeId = $partner . '-' . substr(md5($catalogId . '-' . ($isAddress ? 'address' : 'object')), 0, 8);
        $isOpen = $this->checkIsCatalogNodeOpen($typeId, $typeLevel);
        if ($isOpen) {
            $this->addToList($typeId);
        }
        $children = $this->getCatalogChildren($id, $this->configCatalogOpenNodesLevel, $partner, $catalogId . '-' . $typeId);
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

    public function getCatalogChildren(string $id, int $level, string $partner, string $catalogId, ?string $parentId = null) : array
    {
        $list = [];
        $catalog_api = $this->configApi . '/' . $id . '/hierarchy';
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
                $isOpen = $this->checkIsCatalogNodeOpen($catalogId, $catalogLevel);
                if ($isOpen) {
                    $this->addToList($catalogId);
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
            $isOpen = $this->checkIsCatalogNodeOpen($freeAddressId, $level + 1);
            if ($isOpen) {
                $this->addToList($freeAddressId);
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