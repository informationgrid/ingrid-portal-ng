<?php
namespace Grav\Plugin;
use Grav\Common\Twig\Extension\GravExtension;

class DetailAddressTwigExtension extends GravExtension
{
    public function getName()
    {
        return 'DetailAddressTwigExtension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('getAddressTitle', [$this, 'getAddressTitle']),
            new \Twig_SimpleFunction('getAddressType', [$this, 'getAddressType']),
            new \Twig_SimpleFunction('getAddressSummary', [$this, 'getAddressSummary']),
            new \Twig_SimpleFunction('getAddressLinkRefs', [$this, 'getAddressLinkRefs']),
            new \Twig_SimpleFunction('getAddressContactRefs', [$this, 'getAddressContactRefs']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('filterLinks', [$this, 'filterLinksByKind']),
        ];
    }

    public function getAddressTitle($hit)
    {
        return $hit->getTitle();
    }

    public function getAddressType($hit)
    {
        return $hit->getType();
    }

    public function getAddressSummary($hit)
    {
        return $hit->getSummary();
    }

    public function getAddressLinkRefs($hit)
    {
        return $hit->getLinks();
    }

    public function getAddressContactRefs($hit)
    {
        return $hit->getContacts();
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