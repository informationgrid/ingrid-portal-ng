<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class DetailAddress
{
    var string $theme;

    public function __construct($theme)
    {
        $this->theme = $theme;
    }

    public static function parse(\SimpleXMLElement $content, null|string $uuid, Grav $grav): null|array
    {
        $rootNode = IdfHelper::getNode($content, '//gmd:CI_ResponsibleParty | //idf:idfResponsibleParty');
        if (!is_null($rootNode)) {
            $lang = $grav['language']->getLanguage();
            return DetailAddressParserIdfISO::parse($rootNode, $uuid, $lang);
        }
        return null;
    }

}
