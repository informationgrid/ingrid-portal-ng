<?php

namespace Grav\Plugin;

interface SearchService
{
    public function getSearchResults(string $query, int $page, array $selectedFacets, Grav\Common\Uri $uri, string $lang, string $theme) : ?SearchResult;

    public function getSearchResultsUnparsed(string $query, int $page, array $selectedFacets) : ?array;

}
