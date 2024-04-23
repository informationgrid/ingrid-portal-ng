<?php

namespace Grav\Plugin;

class IdfHelper
{


    public function __construct()
    {

    }

    public static function nodeExists($node, string $xpath)
    {
        for ($node->rewind(); $node->valid(); $node->next()) {
            if ($node->hasChildren()) {
                var_dump($node->current());
            }
        }
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
        $tmpNode = $node->xpath($xpath."/text()");
        if ($tmpNode) {
            return $tmpNode[0];
        }
        return null;
    }

    public static function getNodeList($node, string $xpath)
    {
        $tmpNode = $node->xpath($xpath);
        if ($tmpNode) {
            return $tmpNode;
        }
        return null;
    }

}