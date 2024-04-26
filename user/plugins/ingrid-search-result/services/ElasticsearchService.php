<?php

namespace Grav\Plugin;

use stdClass;

class ElasticsearchService
{

    static function convertToQuery($query): string
    {
        if ($query == "") {
            $result = array( "match_all" => new stdClass());
        } else {
            $result = array("simple_query_string" => array("query" => $query));
        }
        return json_encode(array("query" => $result));
    }
}