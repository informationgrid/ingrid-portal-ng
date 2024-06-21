<?php

namespace Grav\Plugin;

class DetailAddressParser
{

    public function __construct()
    {

    }

    public static function parse($content, $dataSourceName, $provider){
        $rootNode = IdfHelper::getNode($content, '//gmd:CI_ResponsibleParty | //idf:idfResponsibleParty');
        if (!is_null($rootNode)) {
            $uuid = $rootNode->attributes()->uuid;
            return DetailAddressParserIdf::parse($rootNode, $uuid, $dataSourceName, $provider);
        }
    }

}
