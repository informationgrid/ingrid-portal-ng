<?php

namespace Grav\Plugin;

readonly class FacetResult
{

    /**
     * @param string $id
     * @param FacetItem[] $items
     */
    public function __construct(
        public string $id,
        public array  $items,
    )
    {
    }

}
