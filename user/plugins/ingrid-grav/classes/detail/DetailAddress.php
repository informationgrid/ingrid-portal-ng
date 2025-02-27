<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class DetailAddress
{
    public string $theme;

    public function __construct($theme)
    {
        $this->theme = $theme;
    }

    public static function parse(\SimpleXMLElement $content, ?string $uuid): ?DetailAddressISO
    {
        $rootNode = IdfHelper::getNode($content, '//gmd:CI_ResponsibleParty | //idf:idfResponsibleParty');
        if (!is_null($rootNode)) {
            $lang = Grav::instance()['language']->getLanguage();
            return DetailParserAddressIdfISO::parse($rootNode, $uuid, $lang);
        }
        return null;
    }

}
