<?php

namespace Grav\Plugin;

class SearchResultHit
{
   var $uuid;
   var $metaClass;
   var $metaClassName;
   var $partner;
   var $datatypes;
   var $isOpendata;
   var $isInspire;
   var $hasAccessContraint;
   var $title;
   var $url;
   var $summary;
   var $types;
   var $time;
   var $mapUrl;
   var $mapUrlClient;
   var $links;
   var $licences;
   var $bboxes;
   var $wkt;
   var $geom;

    public function __construct()
    {}

    public static function parseHits(array $hits): array
    {
        $output = [];
        foreach ($hits as $hit)
        {
            array_push($output, self::parseHit($hit));
        }
        return $output;
    }

    private static function parseHit($value): SearchResultHit
    {
        $hit = new SearchResultHit();
        $hit->setUuid($value["uuid"] ?? null);
        $hit->setMetaClass($value["metaClass"] ?? null);
        $hit->setMetaClassName($value["metaClassName"] ?? null);
        $hit->setPartner($value["partner"] ?? null);
        $hit->setDatatypes($value["datatypes"] ?? []);
        $hit->setIsOpendata($value["isOpendata"] ?? false);
        $hit->setIsInspire($value["isInspire"] ?? false);
        $hit->setHasAccessContraint($value["hasAccessContraint"] ?? false);
        $hit->setTitle($value["title"] ?? null);
        $hit->setUrl($value["url"] ?? null);
        $hit->setSummary($value["summary"] ?? null);
        $hit->setTypes($value["types"] ?? []);
        $hit->setTime($value["time"] ?? null);
        $hit->setMapUrl($value["mapUrl"] ?? null);
        $hit->setMapUrlClient($value["mapUrlClient"] ?? null);
        $hit->setLinks($value["links"] ?? []);
        $hit->setLicences($value["licences"] ?? []);
        $hit->setBboxes($value["bboxes"] ?? []);
        $hit->setWkt($value["wkt"] ?? null);
        $hit->setGeom($value["geom"] ?? null);
        return $hit;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    public function getMetaClass()
    {
        return $this->metaClass;
    }
    public function setMetaClass($metaClass)
    {
        $this->metaClass = $metaClass;
    }

    public function getMetaClassName()
    {
        return $this->metaClassName;
    }
    public function setMetaClassName($metaClassName)
    {
        $this->metaClassName = $metaClassName;
    }

    public function getPartner()
    {
        return $this->partner;
    }
    public function setPartner($partner)
    {
        $this->partner = $partner;
    }

    public function getDatatypes()
    {
        return $this->datatypes;
    }
    public function setDatatypes($datatypes)
    {
        $this->datatypes = $datatypes;
    }

    public function getIsOpendata()
    {
        return $this->isOpendata;
    }
    public function setIsOpendata($isOpendata)
    {
        $this->isOpendata = $isOpendata;
    }

    public function getIsInspire()
    {
        return $this->isInspire;
    }
    public function setIsInspire($isInspire)
    {
        $this->isInspire = $isInspire;
    }

    public function getHasAccessContraint()
    {
        return $this->hasAccessContraint;
    }
    public function setHasAccessContraint($hasAccessContraint)
    {
        $this->hasAccessContraint = $hasAccessContraint;
    }

    public function getTitle()
    {
        return $this->title;
    }
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getUrl()
    {
        return $this->url;
    }
    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getSummary()
    {
        return $this->summary;
    }
    public function setSummary($summary)
    {
        $this->summary = $summary;
    }

    public function getTypes()
    {
        return $this->types;
    }
    public function setTypes($types)
    {
        $this->types = $types;
    }

    public function getTime()
    {
        return $this->time;
    }
    public function setTime($time)
    {
        $this->time = $time;
    }

    public function getMapUrl()
    {
        return $this->mapUrl;
    }
    public function setMapUrl($mapUrl)
    {
        $this->mapUrl = $mapUrl;
    }

    public function getMapUrlClient()
    {
        return $this->mapUrlClient;
    }
    public function setMapUrlClient($mapUrlClient)
    {
        $this->mapUrlClient = $mapUrlClient;
    }

    public function getLinks()
    {
        return $this->links;
    }
    public function setLinks($links)
    {
        $this->links = $links;
    }

    public function getLicences()
    {
        return $this->licences;
    }
    public function setliCences($licences)
    {
        $this->licences = $licences;
    }

    public function getBboxes()
    {
        return $this->bboxes;
    }
    public function setBboxes($bboxes)
    {
        $this->bboxes = $bboxes;
    }
    public function getWkt()
    {
        return $this->wkt;
    }
    public function setWkt($wkt)
    {
        $this->wkt = $wkt;
    }
    public function getGeom()
    {
        return $this->geom;
    }
    public function setGeom($geom)
    {
        $this->geom = $geom;
    }
}
