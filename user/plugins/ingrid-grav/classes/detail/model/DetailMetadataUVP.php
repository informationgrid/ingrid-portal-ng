<?php

namespace Grav\Plugin;

class DetailMetadataUVP
{

    public string $uuid;
    public ?string $parentUuid;
    public ?string $metaClass;
    public ?string $metaClassName;
    public ?string $title;
    public ?string $summary;
    public ?string $date;
    public ?array $categories;
    public ?array $steps;
    public ?array $negative;
    public ?array $addresses;
    public ?array $bbox;
    public ?string $hasDocs;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }
}