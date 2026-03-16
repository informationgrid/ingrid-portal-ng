<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Common\Utils;
use Grav\Plugin\CapabilitiesHelper;
use Grav\Plugin\CodelistHelper;
use Grav\Plugin\ElasticsearchHelper;
use Grav\Plugin\IdfHelper;
use RocketTheme\Toolbox\Event\Event;

class LfuMis extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onThemeDetailMetadataEvent' => ['addThemeDetailMetadataContent', 0],
            'onThemeSearchHitMetadataEvent' => ['addThemeSearchHitMetadataContent', 0],
        ];
    }

    public function addThemeSearchHitMetadataContent(Event $event): void
    {
        // Get variables from event
        $content = $event['content'];
        $hit = $event['hit'];
        $lang = $event['lang'];

        $hit->folderNames = ElasticsearchHelper::getValue($content, "object_node.tree_path.name");

    }

    public function addThemeDetailMetadataContent(Event $event): void
    {
        // Get variables from event
        $content = $event['content'];
        $hit = $event['hit'];
        $lang = $event['lang'];

        $node = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');

        $xpathExpression = './gmd:dataSetURI/*[self::gco:CharacterString or self::gmx:Anchor]';
        $hit->geodataLink = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = './gmd:identificationInfo/gmd:MD_DataIdentification/gmd:supplementalInformation/*[self::gco:CharacterString or self::gmx:Anchor]';
        $hit->internalNotes = IdfHelper::getNodeValue($node, $xpathExpression);
        $xpathExpression = './idf:treePath/gco:CharacterString';
        $hit->folderNames = IdfHelper::getNodeValue($node, $xpathExpression);
    }



}
