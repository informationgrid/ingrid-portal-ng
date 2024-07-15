<?php

namespace Grav\Plugin;

class CodelistHelper
{


    public function __construct()
    {

    }


    public static function getCodelistEntry(array $codelistIds, string $entryId, string $lang)
    {
        foreach ($codelistIds as $codelistId) {
            $codelist = self::getCodelist($codelistId);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, "//de.ingrid.codelists.model.CodeListEntry[./id = '" . $entryId . "']");
                if ($codelistEntry) {
                    $codelistEntryLang = self::getNode($codelistEntry, "./localisations/" . $lang);
                    return $codelistEntryLang;
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryByIso(array $codelistIds, string $entryId, string $lang)
    {
        foreach ($codelistIds as $codelistId) {
            $codelist = self::getCodelist($codelistId);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, "//de.ingrid.codelists.model.CodeListEntry[./localisations/" . $lang . " = '". $entryId ."']");
                if ($codelistEntry) {
                    $codelistEntryLang = self::getNode($codelistEntry, "./localisations/" . $lang);
                    return $codelistEntryLang;
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryByData(array $codelistIds, string $entryId, string $lang)
    {
        foreach ($codelistIds as $codelistId) {
            $codelist = self::getCodelist($codelistId);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, "//de.ingrid.codelists.model.CodeListEntry[./data = '". $entryId ."']");
                if ($codelistEntry) {
                    $codelistEntryLang = self::getNode($codelistEntry, "./localisations/" . $lang);
                    return $codelistEntryLang;
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryByCompare(array $codelistIds, string $entryId, string $lang, bool $addEqual = true)
    {
        foreach ($codelistIds as $codelistId) {
            $codelist = self::getCodelist($codelistId);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, "//de.ingrid.codelists.model.CodeListEntry[./localisations/" . $lang . " = '". $entryId ."']");
                if ($addEqual) {
                    if ($codelistEntry) {
                        $codelistEntryLang = self::getNode($codelistEntry, "./localisations/" . $lang);
                        if (strcasecmp($codelistEntryLang, $entryId) == 0) {
                            return $codelistEntryLang;
                        }
                    }
                } else {
                    if ($codelistEntry == null) {
                        return $entryId;
                    }
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryData(array $codelistIds, string $entryId)
    {
        foreach ($codelistIds as $codelistId) {
            $codelist = self::getCodelist($codelistId);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, "//de.ingrid.codelists.model.CodeListEntry[./id = '". $entryId ."']");
                if ($codelistEntry) {
                    return self::getNodeValue($codelistEntry, "./data");
                }
            }
        }
        return null;
    }

    private static function getCodelist(string $codelistId){
        $response = file_get_contents('user-data://codelists/codelist_' . $codelistId . '.xml');
        return simplexml_load_string($response);
    }

    public static function getNode($node, string $xpath)
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode[0];
        }
        return null;
    }

    public static function getNodeValue($node, string $xpath)
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return (string) $tmpNode[0];
        }
        return null;
    }

    public static function getNodeList($node, string $xpath)
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode;
        }
        return [];
    }
}