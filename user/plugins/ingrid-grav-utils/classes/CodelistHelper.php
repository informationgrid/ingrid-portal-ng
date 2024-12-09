<?php

namespace Grav\Plugin;
use Grav\Common\Plugin;

class CodelistHelper
{


    public function __construct()
    {

    }


    public static function getCodelistEntry(array $codelistIds, string $entryId, string $lang): null|string
    {
        foreach ($codelistIds as $codelistId) {
            $codelist = self::getCodelist($codelistId);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./id = "' . $entryId . '"]');
                if ($codelistEntry) {
                    $codelistEntryLang = self::getNode($codelistEntry, './localisations/' . $lang);
                    return $codelistEntryLang;
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryByIso(array $codelistIds, string $entryId, string $lang): null|string
    {
        foreach ($codelistIds as $codelistId) {
            $codelist = self::getCodelist($codelistId);
            if (!is_null($codelist)) {
                $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./localisations/iso = "' . $entryId . '"]');
                if ($codelistEntry) {
                    $codelistEntryLang = self::getNode($codelistEntry, './localisations/' . $lang);
                    return $codelistEntryLang;
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryByData(array $codelistIds, string $entryId, string $lang): null|string
    {
        foreach ($codelistIds as $codelistId) {
            $codelist = self::getCodelist($codelistId);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./data = "'. $entryId .'"]');
                if ($codelistEntry) {
                    $codelistEntryLang = self::getNode($codelistEntry, './localisations/' . $lang);
                    return $codelistEntryLang;
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryByCompare(array $codelistIds, string $entryId, string $lang, bool $addEqual = true): null|string
    {
        foreach ($codelistIds as $codelistId) {
            $codelist = self::getCodelist($codelistId);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./localisations/' . $lang . ' = "' . $entryId . '"]');
                if ($addEqual) {
                    if ($codelistEntry) {
                        $codelistEntryLang = self::getNode($codelistEntry, './localisations/' . $lang);
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

    public static function getCodelistEntryData(array $codelistIds, string $entryId): null|string
    {
        foreach ($codelistIds as $codelistId) {
            $codelist = self::getCodelist($codelistId);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./id = "' . $entryId . '"]');
                if ($codelistEntry) {
                    return self::getNodeValue($codelistEntry, './data');
                }
            }
        }
        return null;
    }

    public static function getCodelistPartnerProviders(): array
    {
        $partners = [];
        try {
            $codelistPartner = self::getCodelist('110');
            if ($codelistPartner) {
                $entries = self::getNodeList($codelistPartner, '//de.ingrid.codelists.model.CodeListEntry');
                foreach ($entries as $entry) {
                    $fields = self::getNode($entry, './fields');
                    $id = self::getNodeValue($entry, './id');
                    if ($fields) {
                        $partner = array();
                        $ident = self::getNodeValue($fields, './ident');
                        $name = self::getNodeValue($fields, './name');
                        $partner['name'] = $name;
                        $partner['ident'] = $ident;
                        $partner['providers'] = array();
                        $partners[$id] = $partner;
                    }
                }
            }
            $codelistProvider = self::getCodelist('111');
            if ($codelistProvider) {
                $entries = self::getNodeList($codelistProvider, '//de.ingrid.codelists.model.CodeListEntry');
                foreach ($entries as $entry) {
                    $fields = self::getNode($entry, './fields');
                    $id = self::getNodeValue($entry, './id');
                    if ($fields) {
                        $provider = array();
                        $partnerKey = self::getNodeValue($fields, './sortkey_partner');
                        $ident = self::getNodeValue($fields, './ident');
                        $name = self::getNodeValue($fields, './name');
                        $url = self::getNodeValue($fields, './url');
                        $provider['name'] = $name;
                        $provider['ident'] = $ident;
                        $provider['url'] = $url;
                        if ($partners[$partnerKey]) {
                            $partners[$partnerKey]['providers'][$id]= $provider;
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
        }
        return $partners;
    }

    public static function getCodelistPartners(): array
    {
        $partners = [];
        try {
            $codelistPartner = self::getCodelist('110');
            if ($codelistPartner) {
                $entries = self::getNodeList($codelistPartner, '//de.ingrid.codelists.model.CodeListEntry');
                foreach ($entries as $entry) {
                    $fields = self::getNode($entry, './fields');
                    $id = self::getNodeValue($entry, './id');
                    if ($fields) {
                        $partner = array();
                        $ident = self::getNodeValue($fields, './ident');
                        $name = self::getNodeValue($fields, './name');
                        $partner['name'] = $name;
                        $partner['ident'] = $ident;
                        $partners[$id] = $partner;
                    }
                }
            }
        } catch (\Throwable $th) {
        }
        return $partners;
    }

    private static function getCodelist(string $codelistId): null|\SimpleXMLElement
    {
        try {
            $response = file_get_contents('user-data://codelists/codelist_' . $codelistId . '.xml');
            return simplexml_load_string($response);
        } catch (\Throwable $th) {
        }
        return null;
    }

    public static function getNode(\SimpleXMLElement $node, string $xpath): null|\SimpleXMLElement
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode[0];
        }
        return null;
    }

    public static function getNodeValue(\SimpleXMLElement $node, string $xpath): null|string
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return (string) $tmpNode[0];
        }
        return null;
    }

    public static function getNodeList(\SimpleXMLElement $node, string $xpath): array
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode;
        }
        return [];
    }
}