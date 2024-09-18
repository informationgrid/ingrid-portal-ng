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
        ];
    }

    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('filterLinks', [$this, 'filterLinksByKind']),
        ];
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
}