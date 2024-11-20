<?php

namespace Grav\Plugin;

interface SearchService
{
    public function getSearchResults(string $query, int $page, array $selectedFacets, Grav\Common\Uri $uri, string $lang) : null|SearchResult;

}
