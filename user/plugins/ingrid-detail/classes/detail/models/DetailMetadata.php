<?php

namespace Grav\Plugin;

class DetailMetadata
{

    var $uuid;
    var $type;
    var $title;
    var $alternateTitle;
    var $summary;
    var $timeRefs;
    var $mapsRef;
    var $linkRefs;
    var $useRefs;
    var $infoRefs;
    var $metaInfoRef;
    var $contactRefs;

    public function __construct()
    {

    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    public function getType()
    {
        return $this->Type;
    }

    public function setType($type)
    {
        $this->Type = $type;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getAlternateTitle()
    {
        return $this->alternateTitle;
    }

    public function setAlternateTitle($alternateTitle)
    {
        $this->alternateTitle = $alternateTitle;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function setSummary($summary)
    {
        $this->summary = $summary;
    }

    public function getTimeRefs()
    {
        return $this->timeRefs;
    }

    public function setTimeRefs($timeRefs)
    {
        $this->timeRefs = $timeRefs;
    }

    public function getMapRefs()
    {
        return $this->mapsRef;
    }

    public function setMapRefs($mapsRef)
    {
        $this->mapsRef = $mapsRef;
    }

    public function getLinkRefs()
    {
        return $this->linkRefs;
    }

    public function setLinkRefs($linkRefs)
    {
        $this->linkRefs = $linkRefs;
    }

    public function getUseRefs()
    {
        return $this->useRefs;
    }

    public function setUseRefs($useRefs)
    {
        $this->useRefs = $useRefs;
    }

    public function getInfoRefs()
    {
        return $this->infoRefs;
    }

    public function setInfoRefs($infoRefs)
    {
        $this->infoRefs = $infoRefs;
    }

    public function getMetaInfoRefs()
    {
        return $this->metaInfoRef;
    }

    public function setMetaInfoRefs($metaInfoRef)
    {
        $this->metaInfoRef = $metaInfoRef;
    }

    public function getContactRefs()
    {
        return $this->contactRefs;
    }

    public function setContactRefs($contactRefs)
    {
        $this->contactRefs = $contactRefs;
    }

}
