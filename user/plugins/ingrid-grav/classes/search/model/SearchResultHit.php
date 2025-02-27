<?php

namespace Grav\Plugin;

readonly class SearchResultHit
{

    public function __construct(
        public ?string $uuid,
        public ?string $metaClass,
        public ?string $metaClassName,
        public ?array  $partner,
        public ?array  $datatypes,
        public ?string $isOpendata,
        public ?string $isInspire,
        public ?string $hasAccessConstraint,
        public ?string $title,
        public ?string $url,
        public ?string $summary,
        public ?array  $types,
        public ?string $time,
        public ?string $mapUrl,
        public ?string $mapUrlClient,
        public ?array  $links,
        public ?array  $licences,
        public ?array  $bboxes,
        public ?string $wkt,
        public ?string $geom
    )
    {
    }
}
