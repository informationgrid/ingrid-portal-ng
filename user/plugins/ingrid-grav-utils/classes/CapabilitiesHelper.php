<?php

namespace Grav\Plugin;

class CapabilitiesHelper
{

    public static function getMapUrl(string $url, string $serviceVersion = null, string $serviceType = null, string $additional = null): null|string
    {
        $service = null;
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
            if (is_null($service)) {
                if ($serviceType) {
                    $codelistValue = CodelistHelper::getCodelistEntryByIso(['5100'], $serviceType, 'de');
                    if ($codelistValue) {
                        if (str_contains(strtolower($codelistValue), 'view')) {
                            $service = 'WMS';
                        }
                    }
                }
            }
        } else {
            $service = 'WMTS';
        }
        if(!is_null($service)) {
            if (!stripos($url, 'service=')) {
                $url .= '&SERVICE=' . $service;
            }
            $layersParam = $service . '||' . $url;
            if($additional != null) {
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
            $codelistValue = CodelistHelper::getCodelistEntryByIso("5100", $serviceType,"de");
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