<?php

namespace Grav\Plugin;

readonly class FacetResult
{

    /**
     * @param string $id
     * @param null|string $label
     * @param FacetItem[] $items
     */
    public function __construct(
        public string $id,
        public null|string $label,
        public array  $items,
        public null|int $listLimit,
        public null|string $info,
    )
    {
    }

}
