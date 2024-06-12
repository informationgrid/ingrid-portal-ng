<?php

namespace Grav\Plugin;

readonly class FacetItem
{
    public function __construct(
        public string $value,
        public string $docCount,
    )
    {
    }
}
