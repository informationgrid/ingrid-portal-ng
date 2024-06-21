<?php

namespace Grav\Plugin;

class DetailMetadataParser
{

    public function __construct()
    {

    }

    public static function parse($content, $dataSourceName, $provider) {
       $rootNode = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');
        if (!is_null($rootNode)) {
            $uuid = IdfHelper::getNodeValue($rootNode, './gmd:fileIdentifier/gco:CharacterString');
            return DetailMetadataParserIdf::parse($rootNode, $uuid, $dataSourceName, $provider);
        }
    }

}
