<?php

namespace Grav\Plugin;

class SearchServiceMock implements SearchService
{

    public function getSearchResults($query): SearchResult
    {
        $response = file_get_contents('user-data://test/search/result.json');
        $result = json_decode($response) ?? [];
        return new SearchResult(
            numOfHits: $result->numOfHits ?? 0,
            numOfPages: $result->numOfPages ?? 0,
            numPage: $result->numPage ?? 0,
            hits: SearchResponseTransformerClassic::parseHits($result->hits ?? null)
        );
    }

}