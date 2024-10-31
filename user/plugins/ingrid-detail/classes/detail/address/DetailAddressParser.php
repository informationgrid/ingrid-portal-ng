<?php

namespace Grav\Plugin;

class DetailAddressParser
{

    public function __construct()
    {

    }

    public static function parse($content, $uuid, $lang)
    {
        $rootNode = IdfHelper::getNode($content, '//gmd:CI_ResponsibleParty | //idf:idfResponsibleParty');
        if (!is_null($rootNode)) {
            return DetailAddressParserIdf::parse($rootNode, $uuid, $lang);
        }
    }

}
