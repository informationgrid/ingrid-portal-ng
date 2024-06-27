<?php

namespace Grav\Plugin;

class IdfHelper
{


    public function __construct()
    {

    }

    public static function getNode($node, string $xpath)
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode[0];
        }
        return null;
    }

    public static function getNodeValue($node, string $xpath)
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return (string) $tmpNode[0];
        }
        return null;
    }

    public static function getNodeList($node, string $xpath)
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode;
        }
        return [];
    }

    public static function getNodeValueList($node, string $xpath)
    {
        $array = array();
        $tmpNodes = $node->xpath($xpath);
        foreach ($tmpNodes as $tmpNode) {
            if($tmpNode) {
            array_push($array, (string) $tmpNode);
            }
        }
        return $array;
    }

}