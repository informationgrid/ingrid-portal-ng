<?php

namespace Grav\Plugin;

class DetailMetadataParser
{

    public function __construct()
    {

    }

    public static function parse(\SimpleXMLElement $content, string $uuid, string $dataSourceName, null|string $provider, string $lang)
    {
        $rootNode = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');
        if (!is_null($rootNode)) {
            return DetailMetadataParserIdf::parse($rootNode, $uuid, $dataSourceName, $provider, $lang);
        }
        $rootNode = $content->{'body'};
        if (!is_null($rootNode)) {
            return DetailGenericParserIdf::parse($rootNode, $uuid);
        }
    }

}
