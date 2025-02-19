<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class HitOverview
{
    var Grav $grav;
    var string $lang;
    var int $hitsNum;
    var bool $sortByDate;
    var array $addToSearch;
    var string $theme;

    public function __construct(Grav $grav)
    {
        $this->grav = $grav;
        $this->lang = $this->grav['language']->getLanguage();
        $this->theme = $this->grav['config']->get('system.pages.theme');
        $this->hitsNum = $this->grav['config']->get('themes.' . $this->theme . '.home.hits.num') ?? 0;
        $this->addToSearch = $this->grav['config']->get('themes.' . $this->theme . '.home.hits.add_to_search') ?: [];
        $this->sortByDate = $this->grav['config']->get('themes.' . $this->theme . '.home.hits.sort.sortByDate') ?? 0;
    }

    public function getContent(): null|SearchResult
    {
        if ($this->hitsNum > 0) {
            $service = new SearchServiceImpl($this->grav, $this->hitsNum, [], $this->addToSearch, $this->sortByDate);
            return $service->getSearchResults("", 1, [], $this->grav['uri'], $this->lang, $this->theme);
        }
        return null;
    }
}