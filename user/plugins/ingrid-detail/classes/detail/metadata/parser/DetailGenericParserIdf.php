<?php

namespace Grav\Plugin;

class DetailGenericParserIdf
{

    public static function parse($node, $uuid){
        echo "<script>console.log('InGrid Detail parse HTML with " . $uuid . "');</script>";

        $metadata = array();
        if ($node->children()) {
            $metadata["html"] = $node->children()->asXML();
        }
        return $metadata;
    }

}
