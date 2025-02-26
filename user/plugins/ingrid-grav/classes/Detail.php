<?php

namespace Grav\Plugin;

use Grav\Common\Grav;
use GuzzleHttp\Client;

class Detail
{
    var Grav $grav;
    var string $configApi;
    var string $lang;
    var string $uuid;
    var string $type;
    var string $cswUrl;
    var string $theme;
    var string $timezone;
    var array $hit;
    var array $partners;

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
        $this->hit = [];
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
                    $this->source = $hits[0]->_source;
                    if ($this->source) {
                        $response = $this->source->idf;
                        $dataSourceName = $this->source->dataSourceName;
                        $this->partners = $this->source->partner;
                        $tmpProviders = $this->source->provider;
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
                $source = $hits[0]->_source;
                $response = $source->idf;
                $plugId = $source->iPlugId;
                $title = $source->title;
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

    private function getResponseContent(string $api, string $uuid, string $type): null|string
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
        $indexField = "t01_object.obj_id";
        if($type == "address") {
            $indexField = "t02_address.adr_id";
        }
        $query = array("query" => array("query_string" => array("query" => $indexField . ":\"" . $uuid . "\"")));
        return json_encode($query);
    }

}