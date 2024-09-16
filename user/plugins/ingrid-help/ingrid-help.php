<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use DOMDocument;

/**
 * Class InGridHelpPlugin
 * @package Grav\Plugin
 */
class InGridHelpPlugin extends Plugin
{

    var string $hKey;
    var string $lang;

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                // Uncomment following line when plugin requires Grav < 1.7
                // ['autoload', 100000],
                ['onPluginsInitialized', 0]
            ]
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        $uri = $this->grav['uri'];
        $config = $this->config();
        $this->hkey = $this->grav['uri']->query('hkey');
        if (!$this->hkey) {
            $this->hkey = $config['default_hkey'];
        }
        $this->lang = $this->grav['language']->getActive() ?? $this->grav['language']->getDefault();

        $route = $config['route'] ?? null;
        if ($route && $route == $uri->path()) {
            // Enable the main events we are interested in
            $this->enable([
                'onPageInitialized' => ['onPageInitialized', 0],
                'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
            ]);
        }
    }

    public function onPageInitialized(): void
    {
        echo '<script>console.log("Help");</script>';
    }

    public function onTwigSiteVariables()
    {

        if (!$this->isAdmin()) {

            libxml_use_internal_errors(true);
            // Content
            $theme = $this->grav['theme']->name;
            $xmlContent = new \DOMDocument();
            $xmlContent->load('theme://config/help/ingrid-portal-help_' . $this->lang . '.xml');
            libxml_clear_errors();

            // Help side menu xsl
            $procMenu = new \XSLTProcessor;
            $xslMenu = new \DOMDocument();
            $xslMenu->load('theme://config/help/ingrid-portal-help-menu.xsl');
            $procMenu->importStylesheet($xslMenu);
            $helpMenu = $procMenu->transformToXML($xmlContent);

            // Help xsl
            $procContent = new \XSLTProcessor;
            $xslContent = new \DOMDocument();
            $xslContent->load('theme://config/help/ingrid-portal-help.xsl');
            $procContent->importStylesheet($xslContent);

            if ($this->hkey) {
                $xpath = simplexml_load_string($xmlContent->saveXML());
                if ($xpath) {
                    $xmlQueryContent = $xpath->xpath('//section[@help-key="' . $this->hkey . '"]/ancestor::chapter');
                    if ($xmlQueryContent) {
                        $dom = new \DOMDocument;
                        $dom->loadXML($xmlQueryContent[0]->asXML());
                        $xmlContent = $dom;
                    }
                }
            }

            $helpContent = $procContent->transformToXML($xmlContent);
            $this->grav['twig']->twig_vars['help_content'] = str_replace('<?xml version="1.0"?>', '', $helpContent);
            $this->grav['twig']->twig_vars['help_menu'] = str_replace('<?xml version="1.0"?>', '', $helpMenu);
        }
    }

}
