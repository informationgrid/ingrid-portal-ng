<?php

namespace Grav\Plugin;

class SearchResult
{
    var int $numOfHits;
    var int $numOfPages;
    var int $numPage;
    /** @var SearchResultHit[] */
    var array $hits;


    public function getNumOfHits(): int
    {
        return $this->numOfHits;
    }
    
    public function setNumOfHits($numOfHits): void
    {
        $this->numOfHits = $numOfHits;
    }
    
    public function getNumOfPages(): int
    {
        return $this->numOfPages;
    }
    
    public function setNumOfPages($numOfPages): void
    {
        $this->numOfPages = $numOfPages;
    }

    public function getNumPage(): int
    {
        return $this->numPage;
    }
    
    public function setNumPage($numPage): void
    {
        $this->numPage = $numPage;
    }

    public function setHits($hits): void
    {
        $this->hits = $hits;
    }
}
