<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class DetailAddressParser
{

    public function __construct()
    {

    }

    public static function parse(\SimpleXMLElement $content, null|string $uuid, Grav $grav): null|array
    {
        $rootNode = IdfHelper::getNode($content, '//gmd:CI_ResponsibleParty | //idf:idfResponsibleParty');
        if (!is_null($rootNode)) {
            $lang = $grav['language']->getLanguage();
            return DetailAddressParserIdf::parse($rootNode, $uuid, $lang);
        }
        return null;
    }

}
