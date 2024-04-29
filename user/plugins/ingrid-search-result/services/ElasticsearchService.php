<?php

namespace Grav\Plugin;

use stdClass;

class ElasticsearchService
{

    static function convertToQuery(string $query, $aggs, int $page, int $hitsNum): string
    {
        if ($query == "") {
            $result = array("match_all" => new stdClass());
        } else {
            $result = array("simple_query_string" => array("query" => $query));
        }
        return json_encode(array(
            "from" => $page * $hitsNum,
            "size" => $hitsNum + 5,
            "query" => $result,
            "aggs" => $aggs
        ));
    }
}