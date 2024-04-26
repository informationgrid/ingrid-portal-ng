<?php

namespace Grav\Plugin;

use Grav\Common\GPM\Response;

class SearchResult
{
    /** @var int */
    var $numOfHits;
    /** @var int */
    var $numOfPages;
    /** @var int */
    var $numPage;
    /** @var SearchResultHit[] */
    var $hits;


    public function getNumOfHits()
    {
        return $this->numOfHits;
    }
    
    public function setNumOfHits($numOfHits)
    {
        $this->numOfHits = $numOfHits;
    }
    
    public function getNumOfPages()
    {
        return $this->numOfPages;
    }
    
    public function setNumOfPages($numOfPages)
    {
        $this->numOfPages = $numOfPages;
    }

    public function getNumPage()
    {
        return $this->numPage;
    }
    
    public function setNumPage($numPage)
    {
        $this->numPage = $numPage;
    }

    public function getHits() : array
    {
        return $this->hits ?? [];
    }
    
    public function setHits($hits)
    {
        $this->hits = $hits;
    }
}
