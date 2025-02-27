<?php

namespace Grav\Plugin;

class DetailAddressISO
{

    public string $uuid;
    public ?string $addressClass;
    public ?string $title;
    public ?string $summary;
    public ?array  $links;
    public ?array  $contacts;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }
}