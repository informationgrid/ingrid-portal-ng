<?php

namespace Grav\Plugin;

class DetailMetadataParser
{

    public function __construct()
    {

    }

    public static function parse($content){
        $rootNode = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');
        if (!is_null($rootNode)) {
            $uuid = IdfHelper::getNode($rootNode, './gmd:fileIdentifier/gco:CharacterString/text()');
            return DetailMetadataParserIdf::parse($rootNode, $uuid);
        }
    }

}
