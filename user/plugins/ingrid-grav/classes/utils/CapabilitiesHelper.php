<?php

namespace Grav\Plugin;

class CapabilitiesHelper
{

    public static function getCapabilitiesUrl(string $url, null|string $serviceVersion = null, null|string $serviceType = null): null|string
    {
        if ($serviceVersion) {
            $tmpService = self::extractServiceFromServiceTypeVersion($serviceVersion);
            if ($tmpService) {
                $service = $tmpService;
            }
        }
        if (isset($service) && str_contains($service, " ")) {
            return $url;
        }
        if (strpos($url, '?')) {
            if (!stripos($url, 'request=getcapabilities')) {
                $url .= '&REQUEST=GetCapabilities';
            }
            if (!isset($service)) {
                if ($serviceType) {
                    $codelistValue = CodelistHelper::getCodelistEntryByIso(['5100'], $serviceType, 'de');
                    if (empty($codelistValue)) {
                        $service = $serviceType;
                    }
                }
            }
        } else {
            $service = 'WMTS';
        }
        if (isset($service)) {
            if (strpos($url, '?')) {
                if (!stripos($url, 'service=')) {
                    $url .= '&SERVICE=' . $service;
                }
            }
            return $url;
        } else if (!empty($url) && isset($serviceType) && strcasecmp($serviceType, "view")){
            $defaultService = "WMS";
            if (strpos($url, '?')) {
                if (!stripos($url, 'service=')) {
                    $url .= '&SERVICE=' . $defaultService;
                }
            }
            return $url;
        }
        return $url;
    }

    public static function getMapUrl(string $url, null|string $serviceVersion = null, null|string $serviceType = null, null|string $additional = null): null|string
    {
        if ($serviceVersion) {
            $tmpService = self::extractServiceFromServiceTypeVersion($serviceVersion);
            if ($tmpService) {
                if(str_contains(strtolower($tmpService), 'wms') || str_contains(strtolower($tmpService), 'wmts')) {
                    $service = $tmpService;
                }
            }
        }
        if (strpos($url, '?')) {
            if (!stripos($url, 'request=getcapabilities')) {
                $url .= '&REQUEST=GetCapabilities';
            }
            if (isset($service)) {
                if ($serviceType) {
                    $codelistValue = CodelistHelper::getCodelistEntryByIso(['5100'], $serviceType, 'de');
                    if (empty($codelistValue)) {
                        $service = $serviceType;
                    }
                }
            }
        } else {
            $service = 'WMTS';
        }
        if (isset($service)) {
            if (!stripos($url, 'service=')) {
                $url .= '&SERVICE=' . $service;
            }
            $layersParam = $service . '||' . $url;
            if ($additional != null) {
                $layersParam .= '||' . $additional;
            }
            return urlencode($layersParam);
        } else if (!empty($url) && ($serviceType === "view")) {
            $defaultService = "WMS";
            if (str_contains('?', $url)) {
                if (str_contains('service=', strtolower($url))) {
                   $url .= '&SERVICE=' . $defaultService;
                }
            }
            $layersParam = $defaultService . '||' . $url;
            if ($additional != null) {
                $layersParam .= '||' . $additional;
            }
            return urlencode($layersParam);
        }
        return null;
    }

    public static function extractServiceFromServiceTypeVersion(string $serviceTypeVersion): null|string {
        $splitVersion = explode(",", $serviceTypeVersion);
        $i = 0;
        $tmpVersion = $splitVersion[$i];
        $hasLetters = StringHelper::containsLetters($tmpVersion);
        while(!$hasLetters) {
            $i++;
            if(count($splitVersion) > $i) {
                $tmpVersion = $splitVersion[$i];
                $hasLetters = StringHelper::containsLetters($tmpVersion);
            } else {
                break;
            }
        }
        preg_match('/((?<=\\:| )|^)([a-zA-Z]+?)( [0-9]|$|,)/i', $tmpVersion, $matches);
        if (StringHelper::containsOnlyLetters($tmpVersion) && $matches) {
            if (!str_contains(strtolower($tmpVersion), 'ogc ') || !str_contains(strtolower($tmpVersion), 'ogc:')) {
                $match = $matches[3];
                if ($match) {
                    return $match;
                }
            } else {
                return $tmpVersion;
            }
        } else if (StringHelper::containsLetters($tmpVersion) && $matches) {
            $match = $matches[2];
            if ($match) {
                return $match;
            }
        }
        return null;
    }

    public static function getHitServiceType(null|string $serviceTypeVersion, null|string $serviceType): null|string
    {
        if (!empty($serviceTypeVersion)) {
            $service = self::extractServiceFromServiceTypeVersion($serviceTypeVersion);
            if(!empty($service)) {
                return $service;
            }
        }
        if(!empty($serviceType)) {
            $codelistValue = CodelistHelper::getCodelistEntryByIso(["5100"], $serviceType,"de");
            if (empty($codelistValue)) {
                return $serviceType;
            }
        }
        if(!empty($serviceTypeVersion)) {
            if(StringHelper::containsLetters($serviceTypeVersion)) {
                return $serviceTypeVersion;
            }
        }
        return null;
    }


}