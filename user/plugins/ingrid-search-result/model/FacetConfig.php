<?php

namespace Grav\Plugin;

readonly class FacetConfig
{

    public function __construct(
        public string $id,
        public string $label,
        public array  $query,
    )
    {
    }

}
