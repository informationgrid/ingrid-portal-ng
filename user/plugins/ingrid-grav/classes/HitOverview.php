<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class HitOverview
{
    public Grav $grav;

    public function __construct(Grav $grav)
    {
        $this->grav = $grav;
    }

    public function getContent(): ?SearchResult
    {
        $lang = $this->grav['language']->getLanguage();
        $theme = $this->grav['config']->get('system.pages.theme');
        $searchSettings = $this->grav['config']->get('themes.' . $theme . '.home.hits') ?? [];
        $facetConfig = $searchSettings['facet_config'] ?? [];
        $hitsNum = $searchSettings['hits_num'] ?? 0;
        if ($hitsNum > 0) {
            $service = new SearchServiceImpl($this->grav, $this->grav['uri'], $facetConfig, $searchSettings);
            return $service->getSearchResults("", 1, [], $this->grav['uri'], $lang, $theme);
        }
        return null;
    }
}