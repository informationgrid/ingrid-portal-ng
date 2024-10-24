<?php

namespace Grav\Plugin;

class DetailGenericParserIdf
{

    public static function parse(\SimpleXMLElement $node, string $uuid): array
    {
        echo "<script>console.log('InGrid Detail parse HTML with " . $uuid . "');</script>";

        $metadata = array();
        if ($node->children()) {
            $metadata = [
                'html' => self::getHtmlContent($node->children())
            ];
        }
        return $metadata;
    }

    private static function getHtmlContent(\SimpleXMLElement $nodes): string
    {
        $html = '';
        foreach($nodes as $node) {
            $name = $node->getName();
            $attributes = $node->attributes();
            $text = $node->__toString();

            $html .= '<' . $name;

            foreach ($attributes as $attrKey => $attrValue) {
                $html .= ' ' . $attrKey . '="';
                $html .= $attrValue;
                $html .= '"';
            }
            $html .= '>';

            if ($node->children()) {
                $html .= self::getHtmlContent($node->children());
            }
            $html .= $text;
            $html .= '</' . $name . '>';
        }
        return $html;
    }
}
