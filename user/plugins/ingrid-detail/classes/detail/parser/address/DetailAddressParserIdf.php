<?php

namespace Grav\Plugin;

class DetailAddressParserIdf
{

    public static function parse($node, $uuid){
        echo "<script>console.log('InGrid Detail parse address with " . $uuid . "');</script>";

        $address = new DetailAddress();

        $hierarchyParty = IdfHelper::getNode($node, "./idf:hierarchyParty[@uuid='".$uuid."']");
        $address->setTitle(self::getTitle($hierarchyParty));
        $address->setType(IdfHelper::getNode($hierarchyParty, "./idf:addressType/text()"));
        $address->setSummary(IdfHelper::getNode($node, "./gmd:positionName/gco:CharacterString/text()"));
        $address->setContacts(self::getContacts($node));
        $address->setLinks(self::getLinks($node));
        return $address;
    }



    public static function getTitle($node)
    {
        $type = IdfHelper::getNode($node, "./idf:addressType");
        $title = null;
        $addressIndividualName = IdfHelper::getNode($node, "./idf:addressIndividualName/text()");
        $addressOrganisationName = IdfHelper::getNode($node, "./idf:addressOrganisationName/text()");
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
        return $title;
    }

    public static function getContacts($node)
    {
        $contacts = [];
        $nodes = null;
        if ($node) {
            $nodes = IdfHelper::getNodeList($node, ".");
        }
        
        foreach ($nodes as $tmpNode) {
            $uuid = "";
            $type = "";
            $role = "";
            $roleNode = IdfHelper::getNode($tmpNode, "./gmd:role/gmd:CI_RoleCode/text()");
            if ($roleNode) {
                $role = $roleNode->attributes()->codeListValue; 
            }
            $addresses = [];
            $tmpAddresses = IdfHelper::getNodeList($tmpNode, "./idf:hierarchyParty");
            
            foreach ($tmpAddresses as $tmpAddress) {
                $uuid = $tmpAddress->attributes()->uuid;
                $type = IdfHelper::getNode($tmpAddress, "./idf:addressType/text()");
                $title = IdfHelper::getNode($tmpAddress, "./idf:addressIndividualName/text() | ./gmd:individualName/text()");
                if (!$title) {
                    $title = IdfHelper::getNode($tmpAddress, "./idf:addressOrganisationName/text() | ./gmd:organisationName/gco:CharacterString/text()");
                }
                $item = array (
                    "uuid" => $uuid,
                    "type" => $type,
                    "title" => $title,
                );
                array_push($addresses, $item);
            }

            $streets = [];
            $tmpStreets = IdfHelper::getNodeList($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint/gco:CharacterString/text()");
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
            $postcode = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:postalCode/gco:CharacterString/text()");
            $city = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city/gco:CharacterString/text()");
            $country = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:country/gco:CharacterString/text()");
            $mail = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress/gco:CharacterString/text()");
            $phone = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:voice/gco:CharacterString/text()");
            $facsimile = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:facsimile/gco:CharacterString/text()");
            $url = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:onlineResource/gmd:CI_OnlineResource/gmd:linkage/gmd:URL");
            $service_time = IdfHelper::getNode($tmpNode, "./gmd:contactInfo/gmd:CI_Contact/gmd:hoursOfService/gco:CharacterString/text()");

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
            array_push($contacts, $item);
        }
        return $contacts;
    }

    public static function getLinks($node)
    {
        $links = [];
        $nodes = $node->xpath('idf:objectReference');
        foreach ($nodes as $tmpNode) {
            $uuid = $tmpNode->attributes()->uuid;
            $type = IdfHelper::getNode($tmpNode, "./idf:objectType/text()");
            $title = IdfHelper::getNode($tmpNode, "./idf:objectName/text()");
            $description = IdfHelper::getNode($tmpNode, "./idf:description/text()");
            $attachedToField = IdfHelper::getNode($tmpNode, "./idf:attachedToField/text()");
            $entryId = IdfHelper::getNode($tmpNode, "./idf:attachedToField/@entry-id");
            $serviceType = IdfHelper::getNode($tmpNode, "./idf:serviceType/text()");
            $serviceUrl = IdfHelper::getNode($tmpNode, "./idf:serviceUrl/text()");
            $graphicOverview = IdfHelper::getNodeList($tmpNode, "./idf:graphicOverview/text()");
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
            array_push($links, $item);
        }
        $nodes = $node->xpath('idf:subordinatedParty');
        foreach ($nodes as $tmpNode) {
            $uuid = $tmpNode->attributes()->uuid;
            $type = IdfHelper::getNode($tmpNode, "./idf:addressType/text()");
            $title = DetailAddressParserIdf::getTitle($tmpNode);
            $item = array(
                "uuid" => $uuid,
                "type" => $type,
                "title" => $title,
                "kind" => "subordinated",
            );
            array_push($links, $item);
        }

        $nodes = $node->xpath('idf:superiorParty');
        foreach ($nodes as $tmpNode) {
            $uuid = $tmpNode->attributes()->uuid;
            $type = IdfHelper::getNode($tmpNode, "./idf:addressType/text()");
            $title = DetailAddressParserIdf::getTitle($tmpNode);
            $item = array(
                "uuid" => $uuid,
                "type" => $type,
                "title" => $title,
                "kind" => "superior",
            );
            array_push($links, $item);
        }

        return $links;
    }


}
