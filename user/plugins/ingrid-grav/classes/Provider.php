<?php

namespace Grav\Plugin;

use Grav\Common\Grav;
use GuzzleHttp\Client;

class Provider
{
    var Grav $grav;
    var string $query;

    public function __construct(Grav $grav)
    {
        $this->grav = $grav;

        $theme = $this->grav['config']->get('system.pages.theme');
        $this->query = $this->grav['config']->get('themes.' . $theme . '.provider.query') ?: '';
    }

    public function getContent(): array
    {
        if ($this->query) {
            $hitsNum = 1000;
            $service = new SearchServiceImpl($this->grav, $hitsNum, [], []);
            $results = $service->getSearchResultOriginalHits($this->query, 1, []);
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
            foreach ($results as $result) {
                $hit = $result->_source;
                $hitPartners = $hit->partner;
                $name = self::getValue($hit, 'title');
                $parentName = self::getValue($hit, 't02_address.parents.title');
                if ($parentName) {
                    if (is_array($parentName)) {
                        foreach ($parentName as $parentTitle) {
                            $name = $parentTitle . '<br>' . $name;
                        }
                    } else {
                        $name = $parentName . '<br>' . $name;
                    }
                }
                $exists = in_array($newPartner['ident'], $hitPartners);
                if ($exists && !empty($name)) {
                    $providerExists = in_array($name, array_column($providerList, 'name'));
                    if ( $providerExists === false) {
                        $url = null;
                        $commTypeKeys = self::getValue($hit, 't021_communication.commtype_key');
                        if (is_array($commTypeKeys)) {
                            $existsCommTypeKey = array_search('4', $commTypeKeys);
                            if ($existsCommTypeKey !== false) {
                                $commValues = self::getValue($hit, 't021_communication.comm_value');
                                $url = $commValues[$existsCommTypeKey];
                            }
                        } else {
                            if ($commTypeKeys == '4') {
                                $commValues = self::getValue($hit, 't021_communication.comm_value');
                                $url = $commValues;
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

    private function getValue($value, string $key): mixed
    {
        if (property_exists($value, $key)) {
            return $value -> $key;
        }
        return null;
    }
}