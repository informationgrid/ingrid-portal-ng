<?php

namespace Grav\Plugin;
use Grav\Common\Grav;

interface DetailCreateZipService
{
    public function parse(\SimpleXMLElement $content) : null|array;

}
