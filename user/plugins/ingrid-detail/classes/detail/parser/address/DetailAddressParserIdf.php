<?php

namespace Grav\Plugin;

class DetailAddressParserIdf
{

    public static function parse($node, $uuid, $dataSourceName, $provider){
        echo "<script>console.log('InGrid Detail parse address with " . $uuid . "');</script>";

        $address = array();
        $address["uuid"] = $uuid;
        $hierarchyParty = IdfHelper::getNode($node, "./idf:hierarchyParty[@uuid='".$uuid."']");
        $address["type"] = IdfHelper::getNodeValue($hierarchyParty, "./idf:addressType");
        self::getTitle($hierarchyParty, $address["type"], $address);
        $address["summary"] = IdfHelper::getNodeValue($node, "./gmd:positionName/*[self::gco:CharacterString or self::gmx:Anchor]");
        self::getContacts($node, $address);
        self::getLinks($node, $address);
        //var_dump($address);
        return $address;
    }

    public static function getTitle($node, $type, &$address)
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
        $address["title"] = $title;
    }

    public static function getContacts($node, &$address)
    {
        $array = array();
        $nodes = null;
        if ($node) {
            $nodes = IdfHelper::getNodeList($node, ".");
        }

        foreach ($nodes as $tmpNode) {
            $uuid = "";
            $type = "";
            $role = "";
            $roleNode = IdfHelper::getNodeValue($tmpNode, "./gmd:role/gmd:CI_RoleCode");
            if ($roleNode) {
                $role = $roleNode->attributes()->codeListValue;
            }
            $addresses = [];
            $tmpAddresses = IdfHelper::getNodeList($tmpNode, "./idf:hierarchyParty");

            foreach ($tmpAddresses as $tmpAddress) {
                $uuid = $tmpAddress->attributes()->uuid;
                $type = IdfHelper::getNodeValue($tmpAddress, "./idf:addressType");
                $title = IdfHelper::getNodeValue($tmpAddress, "./idf:addressIndividualName | ./gmd:individualName");
                if (!$title) {
                    $title = IdfHelper::getNodeValue($tmpAddress, "./idf:addressOrganisationName | ./gmd:organisationName/*[self::gco:CharacterString or self::gmx:Anchor]");
                }
                $item = array (
                    "uuid" => $uuid,
                    "type" => $type,
                    "title" => $title,
                );
                array_push($addresses, $item);
            }

            $streets = [];
            $tmpStreets = IdfHelper::getNodeValueList($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint/*[self::gco:CharacterString or self::gmx:Anchor]");
            foreach ($tmpStreets as $tmpStreet) {
                $tmpArray = explode(',', $tmpStreet);
                foreach ($tmpArray as $tmp) {
                    if (str_starts_with($tmpStreet, 'Postbox ')) {
                        $tmp = str_replace('Postbox ', 'Postfach ', $tmp);
                        array_push($streets, $tmp);
                    } else {
                        array_push($streets, $tmp);
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
                "title" => $title,
                "addresses" => $addresses,
                "streets" => $streets,
                "postcode" => $postcode,
                "city" => $city,
                "country" => $country,
                "mail" => $mail,
                "phone" => $phone,
                "facsimile" => $facsimile,
                "url" => $url,
                "service_time" => $service_time,
            );
            array_push($array, $item);
        }
        $address["contacts"] = $array;
    }

    public static function getLinks($node, &$address)
    {
        $array = array();
        $nodes = IdfHelper::getNodeList($node, 'idf:objectReference');
        foreach ($nodes as $tmpNode) {
            $uuid = $tmpNode->attributes()->uuid;
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
                "title" => $title,
                "description" => $description,
                "attachedToField" => $attachedToField,
                "entryId" => $entryId,
                "serviceType" => $serviceType,
                "serviceUrl" => $serviceUrl,
                "previews" => $graphicOverview,
                "kind" => "object",
            );
            array_push($array, $item);
        }
        $nodes = IdfHelper::getNodeList($node, 'idf:subordinatedParty');
        foreach ($nodes as $tmpNode) {
            $uuid = $tmpNode->attributes()->uuid;
            $type = IdfHelper::getNodeValue($tmpNode, "./idf:addressType");
            $title = DetailAddressParserIdf::getTitle($tmpNode);
            $item = array(
                "uuid" => $uuid,
                "type" => $type,
                "title" => $title,
                "kind" => "subordinated",
            );
            array_push($array, $item);
        }

        $nodes = IdfHelper::getNodeList($node, 'idf:superiorParty');
        foreach ($nodes as $tmpNode) {
            $uuid = $tmpNode->attributes()->uuid;
            $type = IdfHelper::getNodeValue($tmpNode, "./idf:addressType");
            $title = DetailAddressParserIdf::getTitle($tmpNode);
            $item = array(
                "uuid" => $uuid,
                "type" => $type,
                "title" => $title,
                "kind" => "superior",
            );
            array_push($array, $item);
        }
        $address["links"] = $array;
    }
}
