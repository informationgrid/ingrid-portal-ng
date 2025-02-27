<?php

namespace Grav\Plugin;

use Grav\Common\Grav;

class Help
{
    public Grav $grav;
    public string $lang;
    public string $default_hkey;
    public string $helpMenu;
    public string $helpContent;

    public function __construct(Grav $grav)
    {
        $this->grav = $grav;
        $this->lang = $this->grav['language']->getLanguage();
        $theme = $this->grav['config']->get('system.pages.theme');
        $this->default_hkey = $this->grav['config']->get('themes.' . $theme . '.help.default_hkey') ?: 'about-1';
    }

    public function getContent(): void
    {
        $hkey = $this->grav['uri']->query('hkey');
        if (!$hkey) {
            $hkey = $this->default_hkey;
        }

        if ($hkey) {
            libxml_use_internal_errors(true);

            // Content
            $xmlContent = new \DOMDocument();
            $xmlContent->load('theme://config/help/ingrid-portal-help_' . $this->lang . '.xml');
            libxml_clear_errors();

            // Help side menu xsl
            $procMenu = new \XSLTProcessor;
            $xslMenu = new \DOMDocument();
            $xslMenu->load('theme://config/help/ingrid-portal-help-menu.xsl');
            $procMenu->importStylesheet($xslMenu);
            $this->helpMenu = $procMenu->transformToXML($xmlContent);

            // Help xsl
            $procContent = new \XSLTProcessor;
            $xslContent = new \DOMDocument();
            $xslContent->load('theme://config/help/ingrid-portal-help.xsl');
            $procContent->importStylesheet($xslContent);

            $xpath = simplexml_load_string($xmlContent->saveXML());
            if ($xpath) {
                $xmlQueryContent = $xpath->xpath('//section[@help-key="' . $hkey . '"]/ancestor::chapter');
                if ($xmlQueryContent) {
                    $dom = new \DOMDocument;
                    $dom->loadXML($xmlQueryContent[0]->asXML());
                    $xmlContent = $dom;
                }
            }

            $this->helpContent = $procContent->transformToXML($xmlContent);
        }
    }
}