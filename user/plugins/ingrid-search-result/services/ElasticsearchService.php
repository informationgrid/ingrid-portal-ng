<?php

namespace Grav\Plugin;

use stdClass;

class ElasticsearchService
{

    static function convertToQuery($query): string
    {
        $result = new stdClass();
        $result->query = new stdClass();

        if ($query == "") {
            $result->query->match_all = new stdClass();
        } else {
            $result->query->simple_query_string = new stdClass();
            $result->query->simple_query_string->query = $query;
//        $result->query->simple_query_string->fields = "content";
        }
        return json_encode($result);
    }
}