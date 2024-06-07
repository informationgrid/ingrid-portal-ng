<?php

namespace Grav\Plugin;

readonly class SearchResult
{

    public function __construct(
        public int   $numOfHits,
        public int   $numOfPages,
        public int   $numPage,
        /** @var SearchResultHit[] */
        public array $hits,
        public array $facets
    )
    {
    }

}
