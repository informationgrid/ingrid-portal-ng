<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class DetailMetadata
{
    var string $theme;

    public function __construct($theme)
    {
        $this->theme = $theme;
    }

    public function parse(\SimpleXMLElement $content, null|string $uuid, null|string $dataSourceName, null|string $provider, Grav $grav): null|array
    {
        $rootNode = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');
        switch ($this->theme) {
            case 'uvp':
            case 'uvp-ni':
                if (!is_null($rootNode)) {
                    $lang = $grav['language']->getLanguage();

                    return DetailMetadataParserIdfUVP::parse($rootNode, $uuid, $dataSourceName, $provider, $lang, $grav);
                }
            default:
                $rootNode = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');
                if (!$uuid) {
                    $uuid = IdfHelper::getNodeValue($rootNode, "./gmd:fileIdentifier/gco:CharacterString");
                }
                if (!is_null($rootNode)) {
                    $lang = $grav['language']->getLanguage();

                    return DetailMetadataParserIdfISO::parse($rootNode, $uuid, $dataSourceName, $provider, $lang, $grav);
                }
        }

        $rootNode = $content->{'body'};
        if (!is_null($rootNode)) {
            return DetailGenericParserIdf::parse($rootNode, $uuid);
        }
        return null;
    }

}
