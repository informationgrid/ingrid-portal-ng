<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class DetailMetadataParser
{

    public function __construct()
    {

    }

    public static function parse(\SimpleXMLElement $content, null|string $uuid, null|string $dataSourceName, null|string $provider, Grav $grav): null|array
    {
        $rootNode = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');
        if (!$uuid) {
            $uuid = IdfHelper::getNodeValue($rootNode, "./gmd:fileIdentifier/gco:CharacterString");
        }
        if (!is_null($rootNode)) {
            $lang = $grav['language']->getLanguage();

            return DetailMetadataParserIdf::parse($rootNode, $uuid, $dataSourceName, $provider, $lang, $grav);
        }
        $rootNode = $content->{'body'};
        if (!is_null($rootNode)) {
            return DetailGenericParserIdf::parse($rootNode, $uuid);
        }
        return null;
    }

}
