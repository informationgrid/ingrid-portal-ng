<?php
namespace Grav\Plugin;
use Grav\Common\Twig\Extension\GravExtension;

class IngridSearchResultHitTwigExtension extends GravExtension
{
    public function getName()
    {
        return 'IngridSearchResultHitTwigExtension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('getHitUuid', [$this, 'getHitUuid']),
            new \Twig_SimpleFunction('getHitTitle', [$this, 'getHitTitle']),
            new \Twig_SimpleFunction('getHitSummary', [$this, 'getHitSummary']),
            new \Twig_SimpleFunction('getHitClass', [$this, 'getHitClass']),
            new \Twig_SimpleFunction('getHitClassName', [$this, 'getHitClassName']),
            new \Twig_SimpleFunction('getHitDatatypes', [$this, 'getHitDatatypes']),
            new \Twig_SimpleFunction('getHitPartner', [$this, 'getHitPartner']),
            new \Twig_SimpleFunction('getHitIsOpendata', [$this, 'getHitIsOpendata']),
            new \Twig_SimpleFunction('getHitIsInspire', [$this, 'getHitIsInspire']),
            new \Twig_SimpleFunction('getHitHasAccessConstraints', [$this, 'getHitHasAccessConstraints']),
            new \Twig_SimpleFunction('getHitBboxes', [$this, 'getHitBboxes']),
            new \Twig_SimpleFunction('getHitWKT', [$this, 'getHitWKT']),
            new \Twig_SimpleFunction('getHitGeom', [$this, 'getHitGeom']),
            new \Twig_SimpleFunction('getHitLinks', [$this, 'getHitLinks']),
            new \Twig_SimpleFunction('getHitTypes', [$this, 'getHitTypes']),
            new \Twig_SimpleFunction('getHitTime', [$this, 'getHitTime']),
            new \Twig_SimpleFunction('getHitUrl', [$this, 'getHitUrl']),
            new \Twig_SimpleFunction('getHitMapUrl', [$this, 'getHitMapUrl']),
            new \Twig_SimpleFunction('getHitMapUrlClient', [$this, 'getHitMapUrlClient']),
            new \Twig_SimpleFunction('getHitLicences', [$this, 'getHitLicences']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('filterLinks', [$this, 'filterLinksByKind']),
        ];
    }

    public function getHitUuid($hit)
    {
        return $hit->getUuid();
    }

    public function getHitTitle($hit)
    {
        return $hit->getTitle();
    }

    public function getHitSummary($hit)
    {
        return $hit->getSummary();
    }

    public function getHitClass($hit)
    {
        return $hit->getMetaClass();
    }

    public function getHitClassName($hit)
    {
        return $hit->getMetaClassName();
    }

    public function getHitDatatypes($hit)
    {
        return $hit->getDatatypes();
    }

    public function getHitPartner($hit)
    {
        return $hit->getPartner();
    }

    public function getHitIsOpendata($hit)
    {
        return $hit->getIsOpendata();
    }

    public function getHitIsInspire($hit)
    {
        return $hit->getIsInspire();
    }

    public function getHitHasAccessConstraints($hit)
    {
        return $hit->getHasAccessConstraints();
    }

    public function getHitBboxes($hit)
    {
        return $hit->getBboxes();
    }

    public function getHitWKT($hit)
    {
        return $hit->getWKT();
    }

    public function getHitGeom($hit)
    {
        return $hit->getGeom();
    }

    public function getHitLinks($hit)
    {
        return $hit->getLinks();
    }

    public function getHitTypes($hit)
    {
        return $hit->getTypes();
    }

    public function getHitTime($hit)
    {
        return $hit->getTime();
    }

    public function getHitUrl($hit)
    {
        return $hit->getUrl();
    }

    public function getHitMapUrl($hit)
    {
        return $hit->getMapUrl();
    }

    public function getHitMapUrlClient($hit)
    {
        return $hit->getMapUrlClient();
    }

    public function getHitLicences($hit)
    {
        return $hit->getLicences();
    }

    public function filterLinksByKind($links, string $kind): array
    {
        $output = [];
        if($links) {
            $output = array_filter($links, function($v) use ($kind) { 
                return $v->kind == $kind; 
            
            });
        }
        return $output;
    }
}