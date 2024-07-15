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
        ];
    }

    public function addKeyValueToMap($map, $key, $value)
    {
        if ($key && $value) {
            $map[$key] = $value;
        }
        return $map;
    }

    public function convertUrlInText($text)
    {
        $regex = "/((((ftp|https?):\\/\\/)|(w{3}.))[\\-\\w@:%_\\+.~#?,&\\/\\/=]+)[^ ,.]/";
        preg_match($regex, $text, $matches);
        $replaceUrl = "";
        foreach($matches as $match) {
            if (substr_count($match, "(") == substr_count($match, ")")) {
                $replaceUrl = $match;
                break;
            }
        }
        $urlString = $replaceUrl;
        if (str_starts_with($replaceUrl, 'www.')) {
            $urlString = "https://" . $urlString;
        }
        return str_replace($replaceUrl, '<a class="intext" target="_blank" href="' . $urlString . '" title="' . $urlString . '">' . $urlString . '</a>', $text);
    }


    // Filters
    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('filterLinks', [$this, 'filterLinksByKind']),
            new \Twig_SimpleFilter('sortContacts', [$this, 'sortContactsByRole']),
            new \Twig_SimpleFilter('filterConstraintDuplicates', [$this, 'filterConstraintDuplicates']),
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

    public function filterConstraintDuplicates(array $constraints, bool $allowJson = true): array
    {
        if ($constraints) {
            $array = array();
            if ($allowJson == false) {
                foreach ($constraints as $constraint) {
                    if (!str_starts_with($constraint, '{')) {
                        array_push($array, $constraint);
                    }
                }
            } else {
                foreach ($constraints as $index=>$constraint) {
                    if (str_starts_with($constraint, '{')) {
                        $json = json_decode($constraint);
                        if ($json->name && $json->quelle && $json->url && !empty($json->url)) {
                            array_push($array, $constraint);
                            $array = self::checkFurtherConstraints($array, $index, $json);
                        }
                    } else {
                        array_push($array, $constraint);
                    }
                }
            }
            return array_values($array);
        }
        return $constraints;
    }

    public function checkFurtherConstraints(array $constraints, int $index, object $json): array
    {
        if ($index - 2 > -1) {
            $checkValue = $constraints[$index - 2];
            if(strcmp($json->name, $checkValue) == 0 || strcmp("Quellenvermerk: " . $json->quelle, $checkValue) == 0) {
                unset($constraints[$index - 2]);
            }
        }
        if ($index - 1 > -1) {
            $checkValue = $constraints[$index - 1];
            if(strcmp($json->name, $checkValue) == 0 || strcmp("Quellenvermerk: " . $json->quelle, $checkValue) == 0) {
                unset($constraints[$index - 1]);
            }
        }
        return $constraints;
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
                            array_push($array, $contact);
                        }
                    }
                }
            }
            foreach ($contacts as $contact) {
                $tmpRole = $contact["role"];
                if ($tmpRole) {
                    $pos = array_search($tmpRole, $sortList);
                    if ($pos === false) {
                        array_push($array, $contact);
                    }
                }
            }
        }
        return $array;
    }

}
