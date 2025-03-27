<?php

namespace Grav\Plugin;

use Grav\Common\Cache;
use Grav\Common\Grav;

class LayoutZDM
{
    public Grav $grav;
    public string $headerUrl;
    public bool $headerBaseHrefRemove;
    public string $footerUrl;


    public function __construct(Grav $grav)
    {
        $this->grav = $grav;

        $theme = $this->grav['config']->get('system.pages.theme');
        $this->headerUrl = $this->grav['config']->get('themes.' . $theme . '.header.url');
        $this->headerBaseHrefRemove = $this->grav['config']->get('themes.' . $theme . '.header.base_href_remove');
        $this->footerUrl = $this->grav['config']->get('themes.' . $theme . '.footer.url');
    }

    public function getContentHeader(string $folder, ?string $title = null): string
    {
        $lang = $this->grav['language'];
        $url = $this->headerUrl;
        $portal = $this->getPortal($folder);
        $url = str_replace('{PORTAL}', $portal['ident'], $url);
        $url .= $portal['label'];
        $cache = $this->grav['cache'];
        $cacheId = md5($url);
        $header = $this->getCacheData($cache, $cacheId);
        if (empty($header)) {
            if (($response = @file_get_contents($url)) !== false) {
                if ($this->headerBaseHrefRemove) {
                    $response = str_replace('<base href="https://www.kuestendaten.de"/>', '', $response);
                }
                $ingridHead = '<link rel="stylesheet" href="user/themes/zdm/css/style.css" type="text/css">'
                    . '<link rel="stylesheet" href="user/themes/zdm/css/custom.css" type="text/css">';
                if ($folder == 'measure') {
                    $ingridHead .= '<link rel="stylesheet" href="user/themes/zdm/css/measure.css" type="text/css">';
                }
                $response = str_replace('  </head>', $ingridHead . '  </head>', $response);
                $response = str_replace('  </head>', $ingridHead . '  </head>', $response);
                if ($title) {
                    $response = str_replace('<title>ZDM  -   </title>', '<title>' . $title . ' - ZDM</title>', $response);
                } else {
                    if ($folder == 'detail') {
                        $response = str_replace('<title>ZDM  -   </title>', '<title>' . $lang->translate('SEARCH_DETAIL.ERROR_NO_DETAILS_AVAILABLE') . ' - ZDM</title>', $response);
                    }
                }
                $cache->save($cacheId, $response);
                return $response;
            }
        }
        return $header;
    }

    public function getContentFooter(string $folder): string
    {
        $url = $this->footerUrl;
        $portal = $this->getPortal($folder);
        $url = str_replace('{PORTAL}', $portal['ident'], $url);
        $url .= $portal['label'];
        $cache = $this->grav['cache'];
        $cacheId = md5($url);
        $footer = $this->getCacheData($cache, $cacheId);
        if (empty($footer)) {
            if (($response = @file_get_contents($url)) !== false) {
                $cache->save($cacheId, $response);
                return $response;
            }
        }
        return $footer;
    }

    private function getCacheData(Cache $cache, string $cacheId): string
    {
        if ($items = $cache->fetch($cacheId)) {
            return $items;
        }
        return '';
    }

    private function getPortal($folder): array
    {
        $ident = '';
        $label = 'Küstendaten';
        $path = $this->grav['uri']->path();
        $lang = $this->grav['language'];
        switch ($path) {
            case str_starts_with($path, '/NOK'):
                $ident = 'NOK';
                break;
            case str_starts_with($path, '/NSK'):
                $ident = 'NSK';
                break;
            case str_starts_with($path, '/OSK'):
                $ident = 'OSK';
                break;
            case str_starts_with($path, '/Tideems'):
                $ident = 'Tideems';
                break;
            case str_starts_with($path, '/Tideweser'):
                $ident = 'Tideweser';
                break;
            case str_starts_with($path, '/Tideelbe'):
                $ident = 'Tideelbe';
                break;
            default:
                break;
        }
        switch ($folder) {
            case 'measure':
                $label = $lang->translate('PAGES.MEASURE.LABEL');
                break;
            case 'map':
                $label = $lang->translate('PAGES.MAP.LABEL');
                break;
            case 'detail':
                $label = '';
                break;
            default:
                break;
        }
        return array(
            'ident' => $ident,
            'label' => $label
        );
    }
}