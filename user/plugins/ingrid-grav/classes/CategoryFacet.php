<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class CategoryFacet
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
        $searchSettings = $this->grav['config']->get('themes.' . $theme . '.home.categories') ?? [];
        $facetConfig = $searchSettings['facet_config'] ?? [];
        $service = new SearchServiceImpl($this->grav, $this->grav['uri'], $facetConfig, $searchSettings);
        return $service->getSearchResults("", 1, [], $this->grav['uri'], $lang, $theme);
    }
}