<?php

namespace Grav\Plugin;

class CodelistHelper
{


    public function __construct()
    {

    }


    public static function getCodelistEntry(array|string $codelistIds, string $entryId, string $lang): ?string
    {
        if (is_array($codelistIds)) {
            foreach ($codelistIds as $codelistId) {
                $codelist = self::getCodelist($codelistId);
                if ($codelist) {
                    $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./id = "' . $entryId . '"]');
                    if ($codelistEntry) {
                        return self::getNode($codelistEntry, './localisations/' . $lang);
                    }
                }
            }
        } else {
            $codelist = self::getCodelist($codelistIds);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./id = "' . $entryId . '"]');
                if ($codelistEntry) {
                    return self::getNode($codelistEntry, './localisations/' . $lang);
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryByLocalisation(array|string $codelistIds, string $entryId, string $lang): ?string
    {
        if (is_array($codelistIds)) {
            foreach ($codelistIds as $codelistId) {
                $codelist = self::getCodelist($codelistId);
                if ($codelist) {
                    $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./localisations/*/text() = "' . $entryId . '"]');
                    if ($codelistEntry) {
                        return self::getNode($codelistEntry, './localisations/' . $lang);
                    }
                }
            }
        } else {
            $codelist = self::getCodelist($codelistIds);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./localisations/*/text() = "' . $entryId . '"]');
                if ($codelistEntry) {
                    return self::getNode($codelistEntry, './localisations/' . $lang);
                }
            }
        }
        return null;
    }


    public static function getCodelistEntryByIso(array|string $codelistIds, ?string $entryId, string $lang): ?string
    {
        if ($entryId) {
            if (is_array($codelistIds)) {
                foreach ($codelistIds as $codelistId) {
                    $codelist = self::getCodelist($codelistId);
                    if (!is_null($codelist)) {
                        $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./localisations/iso = "' . $entryId . '"]');
                        if ($codelistEntry) {
                            return self::getNode($codelistEntry, './localisations/' . $lang);
                        }
                    }
                }
            } else {
                $codelist = self::getCodelist($codelistIds);
                if (!is_null($codelist)) {
                    $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./localisations/iso = "' . $entryId . '"]');
                    if ($codelistEntry) {
                        return self::getNode($codelistEntry, './localisations/' . $lang);
                    }
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryData(array|string $codelistIds, string $entryId): ?string
    {
        if (is_array($codelistIds)) {
            foreach ($codelistIds as $codelistId) {
                $codelist = self::getCodelist($codelistId);
                if ($codelist) {
                    $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./id = "' . $entryId . '"]');
                    if ($codelistEntry) {
                        return self::getNodeValue($codelistEntry, './data');
                    }
                }
            }
        } else {
            $codelist = self::getCodelist($codelistIds);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./id = "' . $entryId . '"]');
                if ($codelistEntry) {
                    return self::getNodeValue($codelistEntry, './data');
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryByData(array|string $codelistIds, string $entryId, string $lang): ?string
    {
        if (is_array($codelistIds)) {
            foreach ($codelistIds as $codelistId) {
                $codelist = self::getCodelist($codelistId);
                if ($codelist) {
                    $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./data = "' . $entryId . '"]');
                    if ($codelistEntry) {
                        return self::getNode($codelistEntry, './localisations/' . $lang);
                    }
                }
            }
        } else {
            $codelist = self::getCodelist($codelistIds);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./data = "' . $entryId . '"]');
                if ($codelistEntry) {
                    return self::getNode($codelistEntry, './localisations/' . $lang);
                }
            }
        }
        return null;
    }

    public static function getCodelistEntryByIdent(array|string $codelistIds, string $entryId, string $lang): ?string
    {
        if (is_array($codelistIds)) {
            foreach ($codelistIds as $codelistId) {
                $codelist = self::getCodelist($codelistId);
                if ($codelist) {
                    $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./localisations/ident = "' . $entryId . '"]');
                    if ($codelistEntry) {
                        return self::getNode($codelistEntry, './localisations[./ident = "' . $entryId . '"]/name');
                    }
                }
            }
        } else {
            $codelist = self::getCodelist($codelistIds);
            if ($codelist) {
                $codelistEntry = self::getNode($codelist, '//de.ingrid.codelists.model.CodeListEntry[./localisations/ident = "' . $entryId . '"]');
                if ($codelistEntry) {
                    return self::getNode($codelistEntry, './localisations[./ident = "' . $entryId . '"]/name');
                }
            }
        }
        return self::getCodelistEntry($codelistIds, $entryId, $lang);
    }
    public static function getCodelistEntryByCompare(array $codelistIds, string $entryId, string $lang, bool $addEqual = true): ?string
    {
        if (is_array($codelistIds)) {
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
        } else {
            $codelist = self::getCodelist($codelistIds);
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

    private static function getCodelist(string $codelistId): ?\SimpleXMLElement
    {
        try {
            $response = file_get_contents('user-data://codelists/codelist_' . $codelistId . '.xml');
            return simplexml_load_string($response);
        } catch (\Throwable $th) {
        }
        return null;
    }

    public static function getNode(\SimpleXMLElement $node, string $xpath): ?\SimpleXMLElement
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode[0];
        }
        return null;
    }

    public static function getNodeValue(\SimpleXMLElement $node, string $xpath): ?string
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