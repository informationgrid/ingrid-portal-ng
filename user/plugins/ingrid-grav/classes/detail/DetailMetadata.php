<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class DetailMetadata
{
    public string $theme;

    public function __construct($theme)
    {
        $this->theme = $theme;
    }

    public function parse(\SimpleXMLElement $content, ?string $uuid, ?string $dataSourceName, array $providers): null|DetailMetadataISO|DetailMetadataUVP|DetailMetadataHTML
    {
        $rootNode = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');
        switch ($this->theme) {
            case 'uvp':
            case 'uvp-ni':
                if (!is_null($rootNode)) {
                    $lang = Grav::instance()['language']->getLanguage();

                    return DetailParserMetadataIdfUVP::parse($rootNode, $uuid, $dataSourceName, $providers, $lang);
                }
                break;
            default:
                $rootNode = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');
                if (!$uuid) {
                    $uuid = IdfHelper::getNodeValue($rootNode, "./gmd:fileIdentifier/gco:CharacterString");
                }
                if (!is_null($rootNode)) {
                    $lang = Grav::instance()['language']->getLanguage();

                    return DetailParserMetadataIdfISO::parse($rootNode, $uuid, $dataSourceName, $providers, $lang);
                }
                break;
        }

        $rootNode = $content->{'body'};
        if (!is_null($rootNode)) {
            return DetailParserGenericIdf::parse($rootNode, $uuid);
        }
        return null;
    }

}
