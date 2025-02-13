<?php

namespace Grav\Plugin;

readonly class FacetItem
{
    public function __construct(
        public string $value,
        public string $label,
        public string $docCount,
        public string $actionLink,
        public null|string $icon,
        public null|bool $display,
    )
    {
    }
}
