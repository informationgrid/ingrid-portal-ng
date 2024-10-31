<?php
namespace Grav\Plugin;

use Grav\Common\Twig\Extension\GravExtension;

class DetailTwigExtension extends GravExtension
{
    public function getName()
    {
        return 'DetailTwigExtension';
    }

    // Functions
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('addKeyValue', [$this, 'addKeyValueToMap']),
            new \Twig_SimpleFunction('convertUrlInText', [$this, 'convertUrlInText']),
            new \Twig_SimpleFunction('getValueFromCodelist', [$this, 'getValueFromCodelist']),
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

}
