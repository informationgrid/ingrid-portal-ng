<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Common\Utils;
use Grav\Plugin\CapabilitiesHelper;
use Grav\Plugin\CodelistHelper;
use Grav\Plugin\ElasticsearchHelper;
use Grav\Plugin\IdfHelper;
use RocketTheme\Toolbox\Event\Event;

class UpSh extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onThemeDetailMetadataEvent' => ['addThemeDetailMetadataContent', 0],
            //'onThemeSearchHitMetadataEvent' => ['addThemeSearchHitMetadataContent', 0],
        ];
    }

    public function addThemeSearchHitMetadataContent(Event $event): void
    {
        // Get variables from event
        $content = $event['content'];
        $hit = $event['hit'];
        $lang = $event['lang'];

    }

    public function addThemeDetailMetadataContent(Event $event): void
    {
        // Get variables from event
        $content = $event['content'];
        $hit = $event['hit'];
        $lang = $event['lang'];

        $node = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');

        $hit->geometryContext = self::getGeometryContext($node, $lang);
    }

    private static function getGeometryContext(\SimpleXMLElement $node, string $lang): array
    {
        $array = [];
        $tmpNodes = IdfHelper::getNodeList($node, './gmd:spatialRepresentationInfo/igctx:MD_GeometryContext[./*]') ?? [];
        foreach ($tmpNodes as $tmpNode) {
            $tmpSubNodeType = IdfHelper::getNodeValue($tmpNode, './igctx:geometryType/*[self::gco:CharacterString or self::gmx:Anchor]') ?? '';
            $tmpSubNodeName = IdfHelper::getNodeValue($tmpNode, './igctx:geometricFeature/*/igctx:featureName/*[self::gco:CharacterString or self::gmx:Anchor]') ?? '';
            $tmpSubNodeDescription = IdfHelper::getNodeValue($tmpNode, './igctx:geometricFeature/*/igctx:featureDescription/*[self::gco:CharacterString or self::gmx:Anchor]') ?? '';
            $tmpSubNodeFeatures = IdfHelper::getNodeList($tmpNode, './igctx:geometricFeature/*/igctx:featureAttributes/igctx:FeatureAttributes/igctx:attribute/*[./igctx:attributeCode|igctx:attributeContent]') ?? [];
            $features = [];
            foreach ($tmpSubNodeFeatures as $tmpSubNodeFeature) {
                $features[] = array(
                    'code' => IdfHelper::getNodeValue($tmpSubNodeFeature, './igctx:attributeContent/*[self::gco:CharacterString or self::gmx:Anchor] | ./igctx:attributeCode/*[self::gco:CharacterString or self::gmx:Anchor]') ?? '',
                    'description' => IdfHelper::getNodeValue($tmpSubNodeFeature, './igctx:attributeDescription/*[self::gco:CharacterString or self::gmx:Anchor]') ?? ''
                );
            }
            $array[] = array(
                'type' => $tmpSubNodeType,
                'name' => $tmpSubNodeName,
                'description' => $tmpSubNodeDescription,
                'features' => $features
            );
        }
        return $array;
    }

}
