<?php

namespace Grav\Plugin;

readonly class SearchResult
{

    public function __construct(
        public int   $numOfHits,
        public int   $numOfPages,
        public int   $numPage,
        public array $listOfPages,
        /** @var SearchResultHit[] */
        public array $hits,
        /** @var FacetResult[] */
        public array $facets
    )
    {
    }

}
