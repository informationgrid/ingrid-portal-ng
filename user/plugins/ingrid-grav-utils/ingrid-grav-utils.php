<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;

/**
 * Class InGridGravUtilsPlugin
 * @package Grav\Plugin
 */
class InGridGravUtilsPlugin extends Plugin
{

    var string $paramUrl;

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

        $uri_path = $uri->path();
        $routes = $config['routes'] ?? null;
        if ($routes && in_array($uri_path, $routes)) {
            // MIMETYPE request
            if ($uri_path == "/utils/mimetype") {
                $this->paramUrl = $this->grav['uri']->query('url') ?: "";

                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateMimetype', 0],
                ]);
            }
        } else {
            // Check themes config for redirected pages
            $theme = $this->grav['config']->get('system.pages.theme');
            $pages_not_allow = $this->grav['config']->get('themes.' . $theme . '.system.pages_to_404');
            if (in_array($uri_path, (array)$pages_not_allow)) {
                $this->grav->redirect('/error');
            }
        }
    }

    public function renderCustomTemplateMimetype(): void
    {
        $twig = $this->grav['twig'];
        // Use the @theme notation to reference the template in the theme
        $theme_path = $twig->addPath($this->grav['locator']->findResource('theme://templates'));
        try {
            $mimeType = MimeTypeHelper::getUrlMimetype($this->paramUrl);
            $output = $twig->twig()->render($theme_path . '/partials/utils/mimetype.html.twig', [
                'mimeType' => $mimeType
            ]);
            echo $output;
        } catch (\Exception $e) {
            $this->grav['log']->debug($e->getMessage());
        }
        exit();
    }
    public function onPageInitialized(): void
    {
        echo '<script>console.log("Utils");</script>';
    }

    public function onTwigSiteVariables()
    {

        if (!$this->isAdmin()) {

        }
    }

}
