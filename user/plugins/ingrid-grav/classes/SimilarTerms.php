<?php

namespace Grav\Plugin;

use Grav\Common\Cache;
use Grav\Common\Grav;
use PHPUnit\Framework\Exception;

class SimilarTerms
{
    public Grav $grav;
    public string $configApi;
    public string $query;
    public string $lang;
    public string $theme;

    public function __construct(Grav $grav, string $api)
    {
        $this->grav = $grav;
        $this->configApi = $api;
        $this->lang = $grav['language']->getLanguage();
        $this->query = $this->grav['uri']->query('q') ?? "";
        $this->theme = $this->grav['config']->get('system.pages.theme');
    }

    public function getContent(): array
    {
        $similarSearchEnable = $this->grav['config']->get('themes.' . $this->theme . '.hit_search.sns.similar_terms.enabled');
        if (!$similarSearchEnable) {
            return [];
        }

        $cache = $this->grav['cache'];
        $queries = preg_replace('/[()\[\]\'";,.\/{}|:<>?~]/', ' ', $this->query);
        $queries = explode( ' ', $queries);
        $cacheId = md5($this->query);
        $items = $this->getCacheData($cache, $cacheId);

        if (empty($items)) {
            $similarSynonymUrl = $this->configApi . implode(',', $queries) . '&synonyms_only=1';
            $similarSynonymUrl = str_replace('similar.rdf', 'similar.json', $similarSynonymUrl);
            $this->grav['log']->debug('Search synonyms_only similar terms with: ' . $similarSynonymUrl);
            if (($similarSynonymsResponse = @file_get_contents($similarSynonymUrl)) !== false) {
                $synonyms = json_decode($similarSynonymsResponse);
                if (isset($synonyms->results)) {
                    foreach ($synonyms->results as $synonym) {
                        $item = [];
                        $found = array_search(strtolower($synonym), array_map('strtolower', $queries));
                        if ($found !== false) {
                            $similarUrl = $this->configApi . $synonym;
                            $this->grav['log']->debug('Search similar terms with: ' . $similarUrl);
                            if (($response = @file_get_contents($similarUrl)) !== false) {
                                $content = simplexml_load_string($response);
                                $labels = RdfHelper::getNodeValueList($content, '//skos:altLabel[@xml:lang="' . $this->lang . '"]');
                                foreach ($labels as $label) {
                                    $item['chk_' . substr(md5($synonym . $label), 0, 6)] = $label;
                                }
                            } else {
                                $this->grav['log']->error('Search similar terms with: ' . $similarUrl);
                            }
                        }
                        if (!empty($item)) {
                            $items[$queries[$found]] = $item;
                        }
                    }
                }
                if (!empty($items)) {
                    $cache->save($cacheId, $items);
                }
            } else {
                $this->grav['log']->error('Search synonyms_only similar terms with: ' . $similarSynonymUrl);
            }
        } else {
            $this->grav['log']->debug('Get search similar terms from cache.');
        }
        return $items;
    }

    public function updateQueryString(array $params): string
    {
        $url = '';
        $q = $params['q'];
        if (isset($params['action'])) {
            unset($params['action']);
        }
        if (isset($params['q'])) {
            unset($params['q']);
        }

        $cache = $this->grav['cache'];
        $cacheId = md5($q);
        $items = $this->getCacheData($cache, $cacheId);
        if (empty($items)) {
            $items = $this->getContent();
        }
        $replaceQueries = [];
        foreach ($items as $key => $item) {
            $replaceString = '';
            foreach ($params as $paramKey => $paramValue) {
                if (isset($item[$paramKey]) and $paramValue == '1') {
                    $replaceString .= ' OR ' . $item[$paramKey];
                    unset($params[$paramKey]);
                }
            }
            if (!empty($replaceString)) {
                $replaceQueries[$key] = $replaceString;
            }
        }
        foreach ($replaceQueries as $replaceKey => $replaceQuery) {
            $q = preg_replace('/\b'.$replaceKey.'\b/', '(' . $replaceKey . $replaceQuery . ')', $q);
        }
        $params['q'] = $q;
        $query_string[] = http_build_query($params);
        $url .= '?' . join('&', $query_string);

        return $url;
    }

    private function getCacheData(Cache $cache, string $cacheId): array
    {
        if ($items = $cache->fetch($cacheId)) {
            return $items;
        }
        return [];
    }

}