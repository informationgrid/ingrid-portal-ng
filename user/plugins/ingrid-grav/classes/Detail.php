<?php

namespace Grav\Plugin;

use Grav\Common\Grav;
use GuzzleHttp\Client;

class Detail
{
    public Grav $grav;
    public string $configApi;
    public string $lang;
    public string $uuid;
    public string $type;
    public string $cswUrl;
    public string $theme;
    public string $timezone;
    public null|DetailMetadataISO|DetailAddressISO|DetailMetadataHTML|DetailMetadataUVP $hit;
    public array $partners;

    public function __construct(Grav $grav, string $api)
    {
        $this->grav = $grav;
        $this->configApi = $api;
        $this->lang = $grav['language']->getLanguage();
        $this->uuid = $this->grav['uri']->query('docuuid') ?: '';
        $this->type = $this->grav['uri']->query('type') ?: 'metadata';
        $this->cswUrl = $this->grav['uri']->query('cswUrl') ?: '';
        $this->theme = $this->grav['config']->get('system.pages.theme');
        $this->timezone = $this->grav['config']->get('system.timezone') ?: 'Europe/Berlin';
    }

    public function getContent(): void
    {
        $response = null;
        $dataSourceName = null;
        $providers = [];

        if ($this->uuid) {
            $responseContent = $this->getResponseContent($this->configApi, $this->uuid, $this->type);
            if ($responseContent) {
                $hits = json_decode($responseContent)->hits;
                if (count($hits) > 0) {
                    $esHit = $hits[0];
                    if ($esHit) {
                        $response = ElasticsearchHelper::getValue($esHit, 'idf');
                        $dataSourceName = ElasticsearchHelper::getValue($esHit, 'dataSourceName');
                        $this->partners = ElasticsearchHelper::getValueArray($esHit, 'partner');
                        $tmpProviders = ElasticsearchHelper::getValueArray($esHit, 'provider');
                        foreach ($tmpProviders as $provider) {
                            $providers[] = CodelistHelper::getCodelistEntryByIdent(['111'], $provider, $this->lang);
                        }
                    }
                }
            }
        } else if ($this->cswUrl) {
            $response = file_get_contents($this->cswUrl);
        }

        if ($response) {
            $content = simplexml_load_string($response);
            IdfHelper::registerNamespaces($content);

            if ($this->type == "address") {
                $parser = new DetailAddress($this->theme);
                $this->hit = $parser->parse($content, $this->uuid);
            } else {
                $parser = new DetailMetadata($this->theme);
                $this->hit = $parser->parse($content, $this->uuid, $dataSourceName, $providers);
            }
        }
    }

    public function getContentZipOutput(): string
    {
        $output = '';
        $responseContent = $this->getResponseContent($this->configApi, $this->uuid, $this->type);
        if ($responseContent) {
            $hits = json_decode($responseContent)->hits;
            $response = null;
            $plugId = null;
            $title = null;
            if (count($hits) > 0) {
                $esHit = $hits[0];
                $response = ElasticsearchHelper::getValue($esHit, 'idf');
                $plugId = ElasticsearchHelper::getValue($esHit, 'iPlugId');
                $title = ElasticsearchHelper::getValue($esHit,'title');
            }
            if (!empty($response)) {
                $parser = new DetailCreateZipUVPServiceImpl('downloads/zip', $title, $this->uuid, $plugId, $this->grav);
                $content = simplexml_load_string($response);
                IdfHelper::registerNamespaces($content);
                [$fileUrl, $fileSize] = $parser->parse($content);
                $twig = $this->grav['twig'];
                // Use the @theme notation to reference the template in the theme
                $theme_path = $twig->addPath($this->grav['locator']->findResource('theme://templates'));
                $output = $twig->twig()->render($theme_path . '/_rest/detail/createZip.html.twig', [
                    'fileUrl' => $fileUrl,
                    'fileSize' => $fileSize,
                ]);
            }
        }
        return $output;
    }

    private function getResponseContent(string $api, string $uuid, string $type): ?string
    {
        try {
            $client = new Client(['base_uri' => $api]);
            return $client->request('POST', 'portal/search', [
                'body' => $this->transformQuery($uuid, $type)
            ])->getBody()->getContents();
        } catch (\Exception $e) {
            $this->grav['log']->error('Error loading detail with uuid "' . $uuid . '": ' . $e->getMessage());
        }
        return null;
    }

    private function transformQuery(string $uuid, string $type): string
    {
        $theme = $this->grav['config']->get('system.pages.theme');
        $searchSettings = $this->grav['config']->get('themes.' . $theme . '.hit_detail');
        $queryStringOperator = $searchSettings['query_string_operator'] ?? 'AND';
        $sourceInclude = $searchSettings['source']['include'] ?? [];
        $sourceExclude = $searchSettings['source']['exclude'] ?? [];
        $requestedFields = $searchSettings['requested_fields'] ?? [];

        $indexField = 't01_object.obj_id';
        $datatype = '-datatype:address';
        if ($type == 'address') {
            $indexField = 't02_address.adr_id';
            $datatype = 'datatype:address';
        }
        $queryString = array("query_string" => array (
                "query" => $indexField . ':"' . $uuid . '" ' . $datatype,
                "default_operator" => $queryStringOperator,
            )
        );
        $source = [];
        if (!empty($sourceInclude)
            || !empty($sourceExclude)) {
            if (!empty($sourceInclude)) {
                $source['include'] = $sourceInclude;
            }
            if (!empty($sourceExclude)){
                $source['exclude'] = $sourceExclude;
            }
        } else {
            $source = true;
        }
        $query = json_encode(array(
            "query" => $queryString,
            "fields" => $requestedFields,
            "_source" => $source
        ));
        $this->grav['log']->debug('Elasticsearch query detail: ' . $query);
        return $query;
    }

}