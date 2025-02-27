<?php

namespace Grav\Plugin;

use Grav\Common\Utils;

class DetailParserAddressIdfISO
{

    public static function parse(\SimpleXMLElement $node, string $uuid, string $lang): DetailAddressISO
    {
        $address = new DetailAddressISO($uuid);
        $hierarchyParty = IdfHelper::getNode($node, "//idf:hierarchyParty[@uuid='".$uuid."']");
        $type = IdfHelper::getNodeValue($hierarchyParty, "./idf:addressType");
        $address->addressClass = $type;
        $address->title = self::getTitle($hierarchyParty, $address->addressClass);
        $address->summary = IdfHelper::getNodeValue($node, "./gmd:positionName/*[self::gco:CharacterString or self::gmx:Anchor]");
        $address->contacts = self::getContacts($node, $lang);
        $address->links = self::getLinks($node, $lang);
        return $address;
    }

    public static function getTitle(\SimpleXMLElement $node, string $type): ?string
    {
        $title = null;
        $addressIndividualName = IdfHelper::getNodeValue($node, "./idf:addressIndividualName");
        $addressOrganisationName = IdfHelper::getNodeValue($node, "./idf:addressOrganisationName");
        if($type == "2") {
            if($addressIndividualName) {
                $title = $addressIndividualName;
            }
        } else if($type == "3") {
            if($addressIndividualName) {
                $title = $addressIndividualName;
            } else if($addressOrganisationName) {
                $title = $addressOrganisationName;
            }
        } else {
            if($addressOrganisationName) {
                $title = $addressOrganisationName;
            }
        }
        return implode(' ', array_reverse(explode(', ', $title)));
    }

    public static function getContacts(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $nodes = IdfHelper::getNodeList($node, ".");

        foreach ($nodes as $tmpNode) {
            $uuid = "";
            $type = "";
            $role = "";
            $roleNode = IdfHelper::getNode($tmpNode, "./gmd:role/gmd:CI_RoleCode");
            if ($roleNode) {
                $role = IdfHelper::getNodeValue($roleNode, "./@codeListValue");
            }
            $addresses = [];
            $tmpAddresses = IdfHelper::getNodeList($tmpNode, "./idf:hierarchyParty");

            foreach ($tmpAddresses as $tmpAddress) {
                $uuid = IdfHelper::getNodeValue($tmpAddress, "./@uuid");
                $type = IdfHelper::getNodeValue($tmpAddress, "./idf:addressType");
                $title = IdfHelper::getNodeValue($tmpAddress, "./idf:addressIndividualName | ./gmd:individualName");
                if ($title) {
                    $item = array (
                        "uuid" => $uuid,
                        "type" => $type,
                        "title" => implode(' ', array_reverse(explode(', ', $title))),
                    );
                    $addresses[] = $item;
                    $organisation = IdfHelper::getNodeValue($tmpAddress, "./idf:addressOrganisationName | ./gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]");
                    if ($organisation) {
                        $item = array(
                            "type" => $type,
                            "title" => $organisation,
                        );
                        $addresses[] = $item;
                    }
                } else {
                    $title = IdfHelper::getNodeValue($tmpAddress, "./idf:addressOrganisationName | ./gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]");
                    $item = array (
                        "uuid" => $uuid,
                        "type" => $type,
                        "title" => implode(' ', array_reverse(explode(', ', $title))),
                    );
                    $addresses[] = $item;
                }
            }

            $streets = [];
            $tmpStreets = IdfHelper::getNodeValueList($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint/*[self::gco:CharacterString or self::gmx:Anchor]");
            foreach ($tmpStreets as $tmpStreet) {
                $tmpArray = explode(',', $tmpStreet);
                foreach ($tmpArray as $tmp) {
                    if (str_starts_with($tmpStreet, 'Postbox ')) {
                        $tmp = str_replace('Postbox ', 'Postfach ', $tmp);
                        $streets[] = $tmp;
                    } else {
                        $streets[] = $tmp;
                    }
                }
            }
            $postcode = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:postalCode/*[self::gco:CharacterString or self::gmx:Anchor]");
            $city = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city/*[self::gco:CharacterString or self::gmx:Anchor]");
            $country = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:country/*[self::gco:CharacterString or self::gmx:Anchor]");
            $mail = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress/*[self::gco:CharacterString or self::gmx:Anchor]");
            $phone = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:voice/*[self::gco:CharacterString or self::gmx:Anchor]");
            $facsimile = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:facsimile/*[self::gco:CharacterString or self::gmx:Anchor]");
            $url = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:onlineResource/gmd:CI_OnlineResource/gmd:linkage/gmd:URL");
            $service_time = IdfHelper::getNodeValue($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:hoursOfService/*[self::gco:CharacterString or self::gmx:Anchor]");

            $item = array (
                "uuid" => $uuid,
                "type" => $type,
                "role" => $role,
                "addresses" => $addresses,
                "streets" => $streets,
                "postcode" => $postcode,
                "city" => $city,
                "country" => $country ? CountryHelper::getNameFromCode($country, $lang) : null,
                "mail" => $mail,
                "phone" => $phone,
                "facsimile" => $facsimile,
                "url" => $url,
                "service_time" => $service_time,
            );
            $array[] = $item;
        }
        return $array;
    }

    public static function getLinks(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $nodes = IdfHelper::getNodeList($node, 'idf:objectReference');
        foreach ($nodes as $tmpNode) {
            $uuid = IdfHelper::getNodeValue($tmpNode, "./@uuid");
            $type = IdfHelper::getNodeValue($tmpNode, "./idf:objectType");
            $title = IdfHelper::getNodeValue($tmpNode, "./idf:objectName");
            $description = IdfHelper::getNodeValue($tmpNode, "./idf:description");
            $attachedToField = IdfHelper::getNodeValue($tmpNode, "./idf:attachedToField");
            $entryId = IdfHelper::getNode($tmpNode, "./idf:attachedToField/@entry-id");
            $serviceType = IdfHelper::getNodeValue($tmpNode, "./idf:serviceType");
            $serviceUrl = IdfHelper::getNodeValue($tmpNode, "./idf:serviceUrl");
            $graphicOverview = IdfHelper::getNodeValueList($tmpNode, "./idf:graphicOverview");
            $item = array(
                "uuid" => $uuid,
                "type" => $type,
                "type_name" => CodelistHelper::getCodelistEntry(["8000"], $type, $lang),
                "title" => $title,
                "description" => $description,
                "attachedToField" => $attachedToField,
                "entryId" => $entryId,
                "serviceType" => $serviceType,
                "serviceUrl" => $serviceUrl,
                "previews" => $graphicOverview,
                "kind" => "object",
            );
            $array[] = $item;
        }
        $nodes = IdfHelper::getNodeList($node, 'idf:subordinatedParty');
        foreach ($nodes as $tmpNode) {
            $uuid = IdfHelper::getNodeValue($tmpNode, "./@uuid");
            $type = IdfHelper::getNodeValue($tmpNode, "./idf:addressType");
            $title = self::getTitle($tmpNode, $type);
            $item = array(
                "uuid" => $uuid,
                "type" => $type,
                "title" => $title,
                "kind" => "subordinated",
            );
            $array[] = $item;
        }

        $nodes = IdfHelper::getNodeList($node, 'idf:superiorParty');
        foreach ($nodes as $tmpNode) {
            $uuid = IdfHelper::getNodeValue($tmpNode, "./@uuid");
            $type = IdfHelper::getNodeValue($tmpNode, "./idf:addressType");
            $title = DetailParserAddressIdfISO::getTitle($tmpNode, $type);
            $item = array(
                "uuid" => $uuid,
                "type" => $type,
                "title" => $title,
                "kind" => "superior",
            );
            $array[] = $item;
        }
        return Utils::sortArrayByKey($array, "title", SORT_ASC);
    }
}
