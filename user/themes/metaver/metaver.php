<?php
namespace Grav\Theme;

use Grav\Common\Grav;
use Grav\Common\Theme;
use Grav\Common\Utils;
use Grav\Plugin\CapabilitiesHelper;
use Grav\Plugin\CodelistHelper;
use Grav\Plugin\ElasticsearchHelper;
use Grav\Plugin\IdfHelper;
use RocketTheme\Toolbox\Event\Event;

class Metaver extends Theme
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

        $hit->links = self::getLinkRefs($node, $hit->links ?? [], $lang);

    }

    private static function getLinkRefs(\SimpleXMLElement $node, array $list, string $lang): array
    {
        $array = [];

        // Verordnung
        $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[./*/idf:attachedToField[@entry-id='9980']]";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $url = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
            $title = IdfHelper::getNodeValue($tmpNode, "./*/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]");
            $description = IdfHelper::getNodeValue($tmpNode, "./*/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
            $attachedToFieldXPath = "./*/idf:attachedToField";
            $attachedToFieldListId = IdfHelper::getNodeValue($tmpNode, $attachedToFieldXPath . "/@list-id");
            $attachedToFieldEntryId = IdfHelper::getNodeValue($tmpNode, $attachedToFieldXPath . "/@entry-id");
            if ($attachedToFieldListId && $attachedToFieldEntryId) {
                $attachedToField = CodelistHelper::getCodelistEntry($attachedToFieldListId, $attachedToFieldEntryId, $lang);
            } else {
                $attachedToField = IdfHelper::getNodeValue($tmpNode, $attachedToFieldXPath);
            }
            $item = array (
                "url" => $url,
                "title" => $title,
                "description" => $description,
                "attachedToField" => $attachedToField,
                "kind" => "regulation",
            );
            $array[] = $item;
        }

        // Weitere Verweise ohne Verordnung
        $xpathExpression = "./gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine[not(./*/idf:attachedToField[@entry-id='9980']) and not(./*/idf:attachedToField[@entry-id='9990']) and not(./*/idf:attachedToField[@entry-id='5066']) and not(./*/gmd:function/*/@codeListValue='download')][./*]";
        $tmpNodes = IdfHelper::getNodeList($node, $xpathExpression);
        foreach ($tmpNodes as $tmpNode) {
            $url = IdfHelper::getNodeValue($tmpNode, "./*/gmd:linkage/gmd:URL");
            $title = IdfHelper::getNodeValue($tmpNode, "./*/gmd:name/*[self::gco:CharacterString or self::gmx:Anchor]");
            $description = IdfHelper::getNodeValue($tmpNode, "./*/gmd:description/*[self::gco:CharacterString or self::gmx:Anchor]");
            $attachedToFieldXPath = "./*/idf:attachedToField";
            $attachedToFieldListId = IdfHelper::getNodeValue($tmpNode, $attachedToFieldXPath . "/@list-id");
            $attachedToFieldEntryId = IdfHelper::getNodeValue($tmpNode, $attachedToFieldXPath . "/@entry-id");
            if ($attachedToFieldListId && $attachedToFieldEntryId) {
                $attachedToField = CodelistHelper::getCodelistEntry($attachedToFieldListId, $attachedToFieldEntryId, $lang);
            } else {
                $attachedToField = IdfHelper::getNodeValue($tmpNode, $attachedToFieldXPath);
                if (!isset($attachedToField)) {
                    $attachedToField = IdfHelper::getNodeValue($tmpNode, "./*/gmd:function/gmd:CI_OnLineFunctionCode/@codeListValue", ["2000"], $lang);
                }
            }
            $applicationProfile = IdfHelper::getNodeValue($tmpNode, "./*/gmd:applicationProfile/*[self::gco:CharacterString or self::gmx:Anchor]");
            $size = IdfHelper::getNodeValue($tmpNode, "./../gmd:transferSize/gco:Real");
            $item = array (
                "url" => $url,
                "title" => $title ?? $url,
                "description" => $description,
                "attachedToField" => $attachedToField,
                "applicationProfile" => $applicationProfile,
                "linkInfo" => $size ? "[" . $size . "MB]" : null,
                "kind" => "other_exclude_regulation",
            );
            $array[] = $item;
        }
        $array = array_merge($array, $list);
        $config = Grav::instance()['config'];
        $theme = $config->get('system.pages.theme');
        $sortLinksASC = $config->get('themes.' . $theme . '.hit_detail.link_sort_asc') ?? true;
        if ($sortLinksASC) {
            return Utils::sortArrayByKey($array, "title", SORT_ASC);
        }
        return $array;
    }

}
