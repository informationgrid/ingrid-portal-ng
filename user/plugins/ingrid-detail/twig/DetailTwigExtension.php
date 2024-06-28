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
            new \Twig_SimpleFunction('addKeyValue', [$this, 'addKeyValueToMap'])
        ];
    }

    public function addKeyValueToMap($map, $key, $value)
    {
        if ($key && $value) {
            $map[$key] = $value;
        }
        return $map;
    }

    // Filters
    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('filterLinks', [$this, 'filterLinksByKind']),
            new \Twig_SimpleFilter('sortContacts', [$this, 'sortContactsByTyp']),
        ];
    }

    public function filterLinksByKind(array $links, string $kind): array
    {
        $output = [];
        if($links) {
            $output = array_filter($links, function($v) use ($kind) {
                return $v["kind"] == $kind;
            });
        }
        return $output;
    }

    public function sortContactsByTyp(array $contacts, array $sortList): array
    {
        $array = array();
        if($contacts && $sortList) {
            foreach ($sortList as $sort) {
                foreach ($contacts as $contact) {
                    $tmpRole = $contact["role"];
                    if($tmpRole) {
                        if($sort == $tmpRole) {
                            array_push($array, $contact);
                        }
                    }
                }
            }
            foreach ($contacts as $contact) {
                $tmpRole = $contact["role"];
                if($tmpRole) {
                    $pos = array_search($tmpRole, $sortList);
                    if($pos === false) {
                        array_push($array, $contact);
                    }
                }
            }
        }
        return $array;
    }
}