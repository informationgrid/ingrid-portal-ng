<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class DetailCreateZipUVPServiceImpl implements DetailCreateZipService
{
    var string $path;
    var string $filenameProcess;
    var string $filenameStats;
    var string $filenameStatsUpdate;
    var string $filenameZip;
    var string $title;
    var string $uuid;
    var string $plugId;
    var Grav $grav;

    public function __construct(string $path, string $title, string $uuid, string $plugId, Grav $grav)
    {
        $this->title = $title;
        $this->uuid = $uuid;
        $this->plugId = $plugId;
        $this->grav = $grav;

        $locator = $grav['locator'];
        $folderPath = $locator->findResource('user-data://', true);
        $this->path = $folderPath . '/' . $path . '/' . $this->plugId . '/' . $this->uuid;
        $this->filenameProcess = $this->path . '/PROCESS_RUNNING';
        $this->filenameStats = $this->path . '/stats.json';
        $this->filenameStatsUpdate = $this->path . '/stats-update.json';
        $this->filenameZip = $this->path . '/' . $title . '.zip';
    }

    public function parse(\SimpleXMLElement $content): null|array
    {
        if (!file_exists($this->filenameProcess)) {
            if (!file_exists($this->filenameStats)) {
                self::createStatsJson($content, $this->filenameStats);
                self::createZip(json_decode(file_get_contents($this->filenameStats), true));
            } else {
                self::createStatsJson($content, $this->filenameStatsUpdate);
                [$addItems, $deleteItems] = self::compareStats($this->filenameStats, $this->filenameStatsUpdate);
                self::updateZip([$addItems, $deleteItems]);
                rename($this->filenameStatsUpdate, $this->filenameStats);
            }
            unlink($this->filenameProcess);
        }
        if(file_exists($this->filenameZip)) {
            $filesize = filesize($this->filenameZip);
            if ($filesize) {
                return ['rest/getDetailZip?plugid=' . $this->plugId . '&uuid=' . $this->uuid, StringHelper::formatBytes($filesize)];
            }
        }
        return [];
    }

    private function updateZip(array $statsItems): void
    {
        $statsItemsAdd = $statsItems[0];
        $statsItemsDel = $statsItems[0];
        if (!empty($statsItemsAdd) or !empty($statsItemsDel)) {
            $zip = new \ZipArchive();
            if ($zip->open($this->filenameZip, \ZipArchive::CREATE)) {
                foreach ($statsItemsDel as $stats) {
                    $fileName = $stats['name'];
                    $filePath = $stats['path'];
                    $zip->deleteName($filePath . '/' . $fileName);
                }
                foreach ($statsItemsAdd as $stats) {
                    $fileName = $stats['name'];
                    $filePath = $stats['path'];
                    $fileUrl = $stats['link'];
                    $zip->addFromString($filePath . '/' . $fileName, file_get_contents($fileUrl));
                }
                $zip->close();
            }
        }
    }

    private function createZip(array $statsItems): void
    {
        if (!empty($statsItems)) {
            $zip = new \ZipArchive();
            if ($zip->open($this->filenameZip, \ZipArchive::CREATE)) {
                foreach ($statsItems as $stats) {
                    $fileName = $stats['name'];
                    $filePath = $stats['path'];
                    $fileUrl = $stats['link'];
                    $zip->addFromString($filePath . '/' . $fileName, file_get_contents($fileUrl));
                }
                $zip->close();
            }
        }
    }
    private function compareStats(string $filenameStats, string $filenameStatsUpdate): array
    {
        $itemsJson = json_decode(file_get_contents($filenameStats), true);
        $itemsUpdateJson = json_decode(file_get_contents($filenameStatsUpdate), true);
        foreach ($itemsUpdateJson as $key => $item) {
            if ($itemsJson[$key]) {
                $result_array = array_diff($item, $itemsJson[$key]);
                if (empty($result_array[0])) {
                    unset($itemsUpdateJson[$key]);
                    unset($itemsJson[$key]);
                }
            }
        }
        return [$itemsUpdateJson, $itemsJson];
    }

    private function createStatsJson(\SimpleXMLElement $content, string $filename): void
    {
        $array = [];
        $nodes = IdfHelper::getNodeList($content, '//idf:idfMdMetadata/steps/step[docs/doc]');
        if (count($nodes) == 0) {
            $nodes = IdfHelper::getNodeList($content, '//idf:idfMdMetadata[docs/doc]');
        }
        if (count($nodes) > 0) {
            self::createFolder();
            if (is_dir($this->path)) {
                if (!file_exists($this->filenameProcess) or !file_exists($filename)) {
                    self::createFile($this->filenameProcess, date('YmdHis'));
                    foreach ($nodes as $node) {
                        $stepType = IdfHelper::getNodeValue($node , './@type');
                        $stepDate = IdfHelper::getNodeValue($node , './datePeriod/from | ./date/from');
                        $docs = IdfHelper::getNodeList($node, './docs');
                        if (count($docs) > 0) {
                            $stepFolder = date_format(date_create($stepDate), 'Ymd') . '_' . $this->grav['language']->translate('SEARCH_DETAIL.STEPS_UVP_' . strtoupper($stepType));
                            foreach ($docs as $doc) {
                                $links = IdfHelper::getNodeList($doc, './doc');
                                if (count($links) > 0) {
                                    $docType = IdfHelper::getNodeValue($doc, './@type');
                                    $docFolder = '';
                                    if(!empty($docType)) {
                                        $docFolder = $this->grav['language']->translate('SEARCH_DETAIL.UVP_' . strtoupper($stepType) . '_DOC_' . strtoupper($docType));
                                    }
                                    foreach ($links as $link) {
                                        $url = IdfHelper::getNodeValue($link, './link');
                                        $label = IdfHelper::getNodeValue($link, './label');

                                        error_reporting(E_ALL & ~E_WARNING);
                                        $headers = get_headers($url, true);
                                        if (substr($headers[0], 9, 3) == 200) {
                                            $contentType = $headers['Content-Type'];
                                            $extensionType = MimeTypeHelper::getMimetypeExtension($contentType);
                                            if (empty($extensionType)) {
                                                $extensionType = '.html';
                                            }
                                            $contentLength = $headers['Content-Length'];
                                            $lastModified = $headers['Last-Modified'];
                                            $fileName = $label . '.' . $extensionType;
                                            $id = $stepFolder . '/' . $docFolder. '/' . $url . '/' . $fileName;
                                            $path = $stepFolder . '/' . $docFolder;
                                            $array[$id] = array (
                                                'length' => $contentLength,
                                                'link' => $url,
                                                'type' => $contentType,
                                                'modified' => $lastModified,
                                                'name' => $fileName,
                                                'path' => $path,
                                            );
                                        }
                                        error_reporting(E_ALL);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        self::createFile($filename, !empty($array) ? json_encode($array) : '{}');
    }

    private function createFolder(): void
    {
        $dir = $this->path;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    private function createFile(string $filename, string $content): void
    {
        $fp = fopen($filename, 'w');
        fwrite($fp, $content);
        fclose($fp);
    }

}
