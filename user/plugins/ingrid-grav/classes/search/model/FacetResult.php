<?php

namespace Grav\Plugin;

readonly class FacetResult
{

    /**
     * @param string $id
     * @param ?string $label
     * @param FacetItem[] $items
     */
    public function __construct(
        public string $id,
        public ?string $label,
        public array  $items,
        public ?int $listLimit,
        public ?string $info,
        public ?array $toggle,
        public ?bool $open,
        public ?array $openBy,
        public ?array $displayDependOn,
    )
    {
    }

}
