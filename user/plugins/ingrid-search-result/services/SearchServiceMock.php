<?php

namespace Grav\Plugin;

class SearchServiceMock implements SearchService
{

    public function getSearchResults($query): SearchResult
    {
        $response = file_get_contents('user-data://test/search/result.json');
        $result = json_decode($response) ?? [];
        $output = new SearchResult();
        $output->setNumOfHits($result->numOfHits ?? 0);
        $output->setNumOfPages($result->numOfPages ?? 0);
        $output->setNumPage($result->numPage ?? 0);
        $output->setHits(SearchResponseTransformerClassic::parseHits($result->hits ?? null));
        return $output;
    }

}