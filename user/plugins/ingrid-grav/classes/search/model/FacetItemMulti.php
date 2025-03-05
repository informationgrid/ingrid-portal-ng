<?php

namespace Grav\Plugin;

readonly class FacetItemMulti
{
    public function __construct(
        public string $value,
        public string $label,
        public array $items,
        public ?string $icon = null,
        public ?bool $displayOnEmpty = false,
    )
    {
    }
}
