<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class DetailMetadataParser
{

    public function __construct()
    {

    }

    public static function parse(\SimpleXMLElement $content, null|string $uuid, null|string $dataSourceName, null|string $provider, Grav $grav): null|array
    {
        $rootNode = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');
        if (!$uuid) {
            $uuid = IdfHelper::getNodeValue($rootNode, "./gmd:fileIdentifier/gco:CharacterString");
        }
        if (!is_null($rootNode)) {
            $lang = $grav['language']->getLanguage();
            $geo_api_url = getenv('GEO_API_URL') !== false ? getenv('GEO_API_URL') : $grav['config']->get('plugins.ingrid-detail.geo_api_url');
            $geo_api_user = getenv('GEO_API_USER') !== false ? getenv('GEO_API_USER') : $grav['config']->get('plugins.ingrid-detail.geo_api_user');
            $geo_api_pass = getenv('GEO_API_PASS') !== false ? getenv('GEO_API_USER') : $grav['config']->get('plugins.ingrid-detail.geo_api_pass');
            $geo_api = [
                'url' => $geo_api_url,
                'user' => $geo_api_user,
                'pass' => $geo_api_pass,
            ];
            return DetailMetadataParserIdf::parse($rootNode, $uuid, $dataSourceName, $provider, $lang, $geo_api);
        }
        $rootNode = $content->{'body'};
        if (!is_null($rootNode)) {
            return DetailGenericParserIdf::parse($rootNode, $uuid);
        }
        return null;
    }

}
