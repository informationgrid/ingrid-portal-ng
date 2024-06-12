<?php

namespace Grav\Plugin;

class SearchResponseTransformerClassic
{
    public static function parseHits(array $hits): array
    {
        return array_map(self::parseHit(...), $hits);
    }

    /**
     * @param object $aggregations
     * @param FacetConfig[] $config
     * @return FacetResult[]
     */
    public static function parseAggregations(object $aggregations, array $config): array
    {
        $result = array();

        foreach ($config as $facetConfig) {
            $items = array();
            if (property_exists((object)$facetConfig, 'queries')) {
                foreach ($facetConfig['queries'] as $key => $query) {
                    $items[] = new FacetItem($key, ((array)$aggregations)[$key]->doc_count);
                }
            } else {
                foreach (((array)$aggregations)[$facetConfig['id']]->buckets as $bucket) {
                    $items[] = new FacetItem($bucket->key, $bucket->doc_count);
                }
            }
            $result[] = new FacetResult($facetConfig['id'], $items);
        }

        return $result;
    }

    private static function parseHit($esHit): SearchResultHit
    {
        $value = $esHit->_source;
        $bboxes = [];
        if (property_exists($value, "x1")) {
            $bboxes = array($value->x1, $value->y1, $value->x2, $value->y2);
        }
        return new SearchResultHit(
            $value->{"t01_object.obj_id"} ?? null,
            $value->{"t01_object.obj_class"} ?? $value->metaClass ?? null,
            $value->metaClassName ?? null,
            self::toArray($value->partner ?? null),
            $value->datatype ?? [],
            $value->isOpendata ?? false,
            $value->isInspire ?? false,
            $value->hasAccessContraint ?? false,
            $value->title ?? null,
            $value->url ?? null,
            $value->summary ?? null,
            $value->types ?? [],
            $value->{"t01_object.mod_time"} ?? null,
            $value->mapUrl ?? null,
            $value->mapUrlClient ?? null,
            $value->links ?? [],
            $value->licences ?? [],
            $bboxes,
            $value->wkt ?? null,
            $value->geom ?? null
        );
    }

    private static function toArray($value): array
    {
        if (gettype($value) == "array") return $value;
        return array($value);
    }
}
