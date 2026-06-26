<?php
namespace Grav\Theme;

use Grav\Common\File\CompiledYamlFile;
use Grav\Common\Theme;
use Grav\Plugin\ElasticsearchHelper;
use Grav\Plugin\IdfHelper;
use RocketTheme\Toolbox\Event\Event;

class LfuMis extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onThemeInitialized' => ['onThemeInitialized', 0],
            'onThemeDetailMetadataEvent' => ['addThemeDetailMetadataContent', 0],
            'onThemeSearchHitMetadataEvent' => ['addThemeSearchHitMetadataContent', 0],
        ];
    }

    public function onThemeInitialized()
    {
        if (!$this->isAdmin()) {
            // Load default configuration.
            $file = CompiledYamlFile::instance("themes://{$this->name}/config/override/override" . YAML_EXT);

            if ($file->exists()) {
                $themeOverrideConfig = $file->content();
                $this->config->set(
                    "themes.{$this->name}",
                    array_replace($this->config(), $themeOverrideConfig)
                );
            }
        }
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
