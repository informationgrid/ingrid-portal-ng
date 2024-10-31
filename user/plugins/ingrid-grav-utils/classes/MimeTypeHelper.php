<?php

namespace Grav\Plugin;

use GuzzleHttp\Client;

class MimeTypeHelper
{
    public static function getUrlMimetype(string $url): string
    {
        $extension = '';
        if (str_contains(strtolower($url), "service=csw")) {
            $extension = "csw";
        } else if(str_contains(strtolower($url), "service=wms")) {
            $extension = "wms";
        } else if(str_contains(strtolower($url), "service=wfs")) {
            $extension = "wfs";
        } else if(str_contains(strtolower($url), "service=wcs")) {
            $extension = "wcs";
        } else if(str_contains(strtolower($url), "service=wmts") || str_contains(strtolower($url), "wmtscapabilities.xml")) {
            $extension = "wmts";
        }

        return $extension;
    }
}