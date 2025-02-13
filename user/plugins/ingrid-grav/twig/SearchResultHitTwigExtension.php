<?php
namespace Grav\Plugin;
use Grav\Common\Twig\Extension\GravExtension;

class SearchResultHitTwigExtension extends GravExtension
{
    public function getName()
    {
        return 'SearchResultHitTwigExtension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('convertUrlInText', [$this, 'convertUrlInText']),
            new \Twig_SimpleFunction('getActionLinkFromFacets', [$this, 'getActionLinkFromFacets'])
        ];
    }

    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('filterLinks', [$this, 'filterLinksByKind']),
        ];
    }

    public function convertUrlInText(string $text): string
    {
        return StringHelper::convertUrlInText($text);
    }

    public function filterLinksByKind($links, string $kind): array
    {
        $output = [];
        if($links) {
            $output = array_filter($links, function($v) use ($kind) {
                return $v["kind"] == $kind;
            });
        }
        return $output;
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
