<?php

namespace Grav\Plugin;

class DetailMetadataHTML
{

    public string $uuid;
    public string $html;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }
}