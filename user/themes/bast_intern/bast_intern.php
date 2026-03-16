<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Common\Utils;
use Grav\Plugin\CapabilitiesHelper;
use Grav\Plugin\CodelistHelper;
use Grav\Plugin\ElasticsearchHelper;
use Grav\Plugin\IdfHelper;
use RocketTheme\Toolbox\Event\Event;

class BastIntern extends Theme
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

        $xpathExpression = './idf:additionalDataSection[@id="projectInfo"]';
        $hit->projectNumber = IdfHelper::getNodeValue($node, $xpathExpression . '/idf:projectNumber');
        $hit->projectTitle = IdfHelper::getNodeValue($node, $xpathExpression . '/idf:projectTitle');

    }

}
