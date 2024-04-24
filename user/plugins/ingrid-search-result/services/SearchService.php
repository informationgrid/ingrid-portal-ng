<?php

namespace Grav\Plugin;

interface SearchService
{
    public function getSearchResults($query);
    
}