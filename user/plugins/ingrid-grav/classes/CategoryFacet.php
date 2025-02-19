<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class CategoryFacet
{
    var Grav $grav;
    var string $lang;
    var array $facetConfig;
    var array $addToSearch;
    var string $theme;

    public function __construct(Grav $grav)
    {
        $this->grav = $grav;
        $this->lang = $this->grav['language']->getLanguage();
        $this->theme = $this->grav['config']->get('system.pages.theme');
        $this->facetConfig = $this->grav['config']->get('themes.' . $this->theme . '.home.categories.facet_config') ?: [];
        $this->addToSearch = $this->grav['config']->get('themes.' . $this->theme . '.home.categories.add_to_search') ?: [];
    }

    public function getContent(): null|SearchResult
    {
        $service = new SearchServiceImpl($this->grav, 0, $this->facetConfig, $this->addToSearch);
        return $service->getSearchResults("", 1, [], $this->grav['uri'], $this->lang, $this->theme);
    }
}