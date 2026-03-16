<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Plugin\CodelistHelper;
use Grav\Plugin\ElasticsearchHelper;
use Grav\Plugin\IdfHelper;
use RocketTheme\Toolbox\Event\Event;

class BkgMis extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            //'onThemeDetailMetadataEvent' => ['addThemeDetailMetadataContent', 0],
            'onThemeSearchHitMetadataEvent' => ['addThemeSearchHitMetadataContent', 0],
        ];
    }

    public function addThemeDetailMetadataContent(Event $event): void
    {
        // Get variables from event
        $content = $event['content'];
        $hit = $event['hit'];
        $node = IdfHelper::getNode($content, '//gmd:MD_Metadata | //idf:idfMdMetadata');

    }

    public function addThemeSearchHitMetadataContent(Event $event): void
    {
        // Get variables from event
        $content = $event['content'];
        $hit = $event['hit'];
        $lang = $event['lang'];

        $hit->license = self::getLicense($content, $lang);
    }


    private static function getLicense(\stdClass $esHit, string $lang): mixed
    {
        $licenseKey = ElasticsearchHelper::getFirstValue($esHit, "object_use_constraint.license_key");
        $licenseValue = ElasticsearchHelper::getFirstValue($esHit, "object_use_constraint.license_value");

        if ($licenseKey || $licenseValue) {
            if ($licenseKey) {
                $item = json_decode(CodelistHelper::getCodelistEntryData(["10003"], $licenseKey));
                if ($item) {
                    return $item;
                }
                $item = CodelistHelper::getCodelistEntry(["10003"], $licenseKey, $lang);
                if ($item) {
                    return array(
                        "name" => $item
                    );
                }
            }
            if ($licenseValue) {
                if (str_starts_with($licenseValue, '{')) {
                    return json_decode($licenseValue);
                } else {
                    return array(
                        "name" => $licenseValue
                    );
                }
            }
        }
        return null;
    }
}
