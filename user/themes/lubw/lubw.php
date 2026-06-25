<?php
namespace Grav\Theme;

use Grav\Common\File\CompiledYamlFile;
use Grav\Common\Theme;
use Grav\Plugin\CodelistHelper;
use Grav\Plugin\ElasticsearchHelper;
use RocketTheme\Toolbox\Event\Event;

class Lubw extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onThemeInitialized' => ['onThemeInitialized', 0],
            'onThemeDetailHitMetadataWithOtherParamsEvent' => ['onThemeDetailHitMetadataWithOtherParamsEvent', 0],
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
                    array_replace_recursive($this->config(), $themeOverrideConfig)
                );
            }
        }
    }

    public function onThemeDetailHitMetadataWithOtherParamsEvent(Event $event): void
    {
        // Get variables from event
        $uri = $event['uri'];
        $detailController = $event['detailController'];
        $response = '';

        if ($uri && $detailController) {
            $oac = $this->grav['uri']->query('oac') ?? '';
            if ($oac) {
                $responseContent = $detailController->getResponseContent($detailController->configApi, $oac, $detailController->type, 'oac');
                if ($responseContent) {
                    $hits = json_decode($responseContent)->hits;
                    if (count($hits) > 0) {
                        $detailController->esHit = $hits[0];
                        if ($detailController->esHit) {
                            $dataSourceName = ElasticsearchHelper::getValue($detailController->esHit, 't03_catalogue.cat_name') ?? ElasticsearchHelper::getValue($this->esHit, 'dataSourceName');
                            $detailController->partners = ElasticsearchHelper::getValueArray($detailController->esHit, 'partner');
                            $tmpProviders = ElasticsearchHelper::getValueArray($detailController->esHit, 'provider');
                            $detailController->title = ElasticsearchHelper::getValue($detailController->esHit, 'title');
                            foreach ($tmpProviders as $provider) {
                                $providers[] = CodelistHelper::getCodelistEntryByIdent(['111'], $provider, $detailController->lang) ?? $provider;
                            }
                            $detailController->response = ElasticsearchHelper::getValue($detailController->esHit, 'idf');
                        }
                    }
                }
            }
        }
    }

}
