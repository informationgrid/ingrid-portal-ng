<?php
namespace Grav\Plugin;

use Grav\Common\Twig\Extension\GravExtension;

class InGridGravTwigExtension extends GravExtension
{
    public function getName()
    {
        return 'InGridGravTwigExtension';
    }

    // Functions
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('addKeyValue', [$this, 'addKeyValueToMap']),
            new \Twig_SimpleFunction('convertUrlInText', [$this, 'convertUrlInText']),
            new \Twig_SimpleFunction('getValueFromCodelist', [$this, 'getValueFromCodelist']),
            new \Twig_SimpleFunction('getActionLinkFromFacets', [$this, 'getActionLinkFromFacets']),
            new \Twig_SimpleFunction('removeHashLocale', [$this, 'removeHashLocale']),
        ];
    }

    public function addKeyValueToMap($map, $key, $value)
    {
        if ($key && $value) {
            $map[$key] = $value;
        }
        return $map;
    }

    public function convertUrlInText(string $text): string
    {
        return StringHelper::convertUrlInText($text);
    }

    public function getValueFromCodelist(string $codelistId, string $codelistValue, string $lang = 'de'): string {
        $value = CodelistHelper::getCodelistEntryByIso([$codelistId], $codelistValue, $lang);
        if ($value) {
            return $value;
        }
        return $codelistValue;
    }

    public function removeHashLocale(string $text): string
    {
        $check = '#locale-';
        if (str_contains($text, $check)) {
            return explode($check, $text)[0];
        }
        return $text;
    }

    // Filters
    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('filterLinks', [$this, 'filterLinksByKind']),
            new \Twig_SimpleFilter('sortContacts', [$this, 'sortContactsByRole']),
        ];
    }

    public function filterLinksByKind(array $links, string $kind): array
    {
        $output = [];
        if ($links) {
            $output = array_filter($links, function($v) use ($kind) {
                return $v["kind"] == $kind;
            });
        }
        return $output;
    }

    public function sortContactsByRole(array $contacts, array $sortList): array
    {
        $array = array();
        if ($contacts && $sortList) {
            foreach ($sortList as $sort) {
                foreach ($contacts as $contact) {
                    $tmpRole = $contact["role"];
                    if ($tmpRole) {
                        if ($sort == $tmpRole) {
                            $array[] = $contact;
                        }
                    }
                }
            }
            foreach ($contacts as $contact) {
                $tmpRole = $contact["role"];
                if ($tmpRole) {
                    $pos = in_array($tmpRole, $sortList);
                    if ($pos === false) {
                        $array[] = $contact;
                    }
                }
            }
        }
        return $array;
    }

    public function getActionLinkFromFacets($facets, $facetId, $facetValue): string|null {
        for ($i=0; $i < count($facets); $i++) {
            if ($facets[$i]->id == $facetId) {
                for ($j = 0; $j < count($facets[$i]->items); $j++) {
                    if ($facets[$i]->items[$j]->value == $facetValue) {
                        return $facets[$i]->items[$j]->actionLink;
                    }
                }
            }
        }
        return null;
    }
}
