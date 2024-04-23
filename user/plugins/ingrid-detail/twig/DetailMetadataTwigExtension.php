<?php
namespace Grav\Plugin;

use Grav\Common\Twig\Extension\GravExtension;

class DetailMetadataTwigExtension extends GravExtension
{
    public function getName()
    {
        return 'DetailMetadataTwigExtension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('getMetadataTitle', [$this, 'getMetadataTitle']),
            new \Twig_SimpleFunction('getMetadataType', [$this, 'getMetadataType']),
            new \Twig_SimpleFunction('getMetadataSummary', [$this, 'getMetadataSummary']),
            new \Twig_SimpleFunction('getMetadataContactRefs', [$this, 'getMetadataContactRefs']),
            new \Twig_SimpleFunction('getMetadataTimeRefs', [$this, 'getMetadataTimeRefs']),
            new \Twig_SimpleFunction('getMetadataMapRefs', [$this, 'getMetadataMapRefs']),
            new \Twig_SimpleFunction('getMetadataLinkRefs', [$this, 'getMetadataLinkRefs']),
            new \Twig_SimpleFunction('getMetadataUseRefs', [$this, 'getMetadataUseRefs']),
            new \Twig_SimpleFunction('getMetadataInfoRefs', [$this, 'getMetadataInfoRefs']),
            new \Twig_SimpleFunction('getMetadataMetaInfoRefs', [$this, 'getMetadataMetaInfoRefs']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('filterLinks', [$this, 'filterLinksByKind']),
            new \Twig_SimpleFilter('sortContacts', [$this, 'sortContactsByTyp']),
        ];
    }

    public function getMetadataTitle($hit)
    {
        return $hit->getTitle();
    }

    public function getMetadataType($hit)
    {
        return $hit->getType();
    }

    public function getMetadataSummary($hit)
    {
        return $hit->getSummary();
    }

    public function getMetadataTimerefs($hit)
    {
        return $hit->getTimeRefs();
    }

    public function getMetadataMapRefs($hit)
    {
        return $hit->getMapRefs();
    }

    public function getMetadataLinkRefs($hit)
    {
        return $hit->getLinkRefs();
    }

    public function getMetadataUseRefs($hit)
    {
        return $hit->getUseRefs();
    }

    public function getMetadataInfoRefs($hit)
    {
        return $hit->getInfoRefs();
    }

    public function getMetadataMetaInfoRefs($hit)
    {
        return $hit->getMetaInfoRefs();
    }

    public function getMetadataContactRefs($hit)
    {
        return $hit->getContactRefs();
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