<?php

namespace Grav\Plugin;

class SearchResponseTransformerClassic
{
    public static function parseHits(array $hits): array
    {
        return array_map('self::parseHit', $hits);
    }

    private static function parseHit($esHit): SearchResultHit
    {
        $value = $esHit->_source;
        $hit = new SearchResultHit();
        $hit->setUuid($value->{"t01_object.obj_id"} ?? null);
        $hit->setMetaClass($value->metaClass ?? null);
        $hit->setMetaClassName($value->metaClassName ?? null);
        $hit->setPartner($value->partner ?? null);
        $hit->setDatatypes($value->datatype ?? []);
        $hit->setIsOpendata($value->isOpendata ?? false);
        $hit->setIsInspire($value->isInspire ?? false);
        $hit->setHasAccessContraint($value->hasAccessContraint ?? false);
        $hit->setTitle($value->title ?? null);
        $hit->setUrl($value->url ?? null);
        $hit->setSummary($value->summary ?? null);
        $hit->setTypes($value->types ?? []);
        $hit->setTime($value->{"t01_object.mod_time"} ?? null);
        $hit->setMapUrl($value->mapUrl ?? null);
        $hit->setMapUrlClient($value->mapUrlClient ?? null);
        $hit->setLinks($value->links ?? []);
        $hit->setLicences($value->licences ?? []);
        if (property_exists($value, "x1")) {
           $hit->setBboxes(array($value->x1, $value->y1, $value->x2, $value->y2) ?? []);
        }
        $hit->setWkt($value->wkt ?? null);
        $hit->setGeom($value->geom ?? null);
        return $hit;
    }
}