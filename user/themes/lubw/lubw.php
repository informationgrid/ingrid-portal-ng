<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Common\Utils;
use Grav\Plugin\CapabilitiesHelper;
use Grav\Plugin\CodelistHelper;
use Grav\Plugin\DebugHelper;
use Grav\Plugin\ElasticsearchHelper;
use Grav\Plugin\IdfHelper;
use GuzzleHttp\Client;
use RocketTheme\Toolbox\Event\Event;

class Lubw extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onThemeDetailHitMetadataWithOtherParamsEvent' => ['onThemeDetailHitMetadataWithOtherParamsEvent', 0],
        ];
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
