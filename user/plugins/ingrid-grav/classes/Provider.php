<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class Provider
{
    public Grav $grav;
    public string $theme;
    public string $query;

    public function __construct(Grav $grav)
    {
        $this->grav = $grav;

        $this->theme = $this->grav['config']->get('system.pages.theme');
        $this->query = $this->grav['config']->get('themes.' . $this->theme . '.provider.query') ?: '';
    }

    public function getContent(): array
    {
        if ($this->query) {
            $searchSettings = $this->grav['config']->get('themes.' . $this->theme . '.provider') ?? [];
            $service = new SearchServiceImpl($this->grav, $this->grav['uri'], [], $searchSettings);
            $results = $service->getSearchResultsUnparsed($this->query, 1, []);
            return $this->getPartners($results);
        } else {
            return CodelistHelper::getCodelistPartnerProviders();
        }
    }

    private function getPartners(array $results): array
    {
        $list = array();
        $partners = CodelistHelper::getCodelistPartners();
        foreach ($partners as $partner) {
            $newPartner = [
                'name' => $partner['name'],
                'ident' => $partner['ident'],
            ];
            $providerList = array();
            foreach ($results as $esHit) {
                $esHitPartners = ElasticsearchHelper::getValueArray($esHit, 'partner');
                $name = ElasticsearchHelper::getValue($esHit, 'title');
                $parentName = ElasticsearchHelper::getValue($esHit, 't02_address.parents.title');
                if ($parentName) {
                    if (is_array($parentName)) {
                        foreach ($parentName as $parentTitle) {
                            $name = $parentTitle . '<br>' . $name;
                        }
                    } else {
                        $name = $parentName . '<br>' . $name;
                    }
                }
                $exists = in_array($newPartner['ident'], $esHitPartners);
                if ($exists && !empty($name)) {
                    $providerExists = in_array($name, array_column($providerList, 'name'));
                    if ( $providerExists === false) {
                        $url = null;
                        $commTypeKeys = ElasticsearchHelper::getValueArray($esHit, 't021_communication.commtype_key');
                        if (!empty($commTypeKeys)) {
                            $existsCommTypeKey = array_search('4', $commTypeKeys);
                            if ($existsCommTypeKey !== false) {
                                $commValues = ElasticsearchHelper::getValueArray($esHit, 't021_communication.comm_value');
                                $url = $commValues[$existsCommTypeKey];
                            }
                        }
                        if (!empty($url) && !str_starts_with($url, 'http')) {
                            $url = 'https://' . $url;
                        }
                        $provider = [
                            'name' => $name,
                            'url' => $url,
                        ];
                        $providerList[] = $provider;
                    }
                }
            }
            $newPartner['providers'] = $providerList;
            $list[] = $newPartner;
        }
        return $list;
    }
}