<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;

/**
 * Class InGridGravPlugin
 * @package Grav\Plugin
 */
class InGridGravPlugin extends Plugin
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
            ],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
            'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', 0],
            'onPagesInitialized'       => ['onPagesInitialized', 0],
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

        $config = $this->config();
        $uri = $this->grav['uri'];
        $uri_path = $uri->path();
        switch ($uri_path) {
            case '/utils/mimetype':
                $this->paramUrl = $this->grav['uri']->query('url') ?: "";
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateMimetype', 0],
                ]);
                break;
            case '/utils/getUrlFileSize':
                $this->paramUrl = $this->grav['uri']->query('url') ?: "";
                $this->enable([
                    'onPageInitialized' => ['renderCustomTemplateUrlFileSize', 0],
                ]);
                break;

            default:
                // Check themes config for redirected pages
                $this->enable([
                    'onPageInitialized' => ['onPageInitialized', 0],
                ]);
                break;
        }
    }

    public function onTwigTemplatePaths(): void
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    public function onAdminTwigTemplatePaths($event) {
        $event['paths'] = array_merge($event['paths'], [__DIR__ . '/templates']);
        return $event;
    }

    public function renderCustomTemplateUrlFileSize(): void
    {
        try {
            $headers = get_headers($this->paramUrl, true);
            if (substr($headers[0], 9, 3) == 200) {
                $contentLength = $headers['Content-Length'];
                echo StringHelper::formatBytes($contentLength);
            }
        } catch (\Exception $e) {
            $this->grav['log']->debug($e->getMessage());
        }
        exit();
    }

    public function renderCustomTemplateMimetype(): void
    {
        $twig = $this->grav['twig'];
        // Use the @theme notation to reference the template in the theme
        $theme_path = $twig->addPath($this->grav['locator']->findResource('theme://templates'));
        try {
            $mimeType = MimeTypeHelper::getUrlMimetype($this->paramUrl);
            $output = $twig->twig()->render($theme_path . '/_rest/utils/mimetype.html.twig', [
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
        $uri = $this->grav['uri'];
        $uri_path = $uri->path();
        $page = $this->grav['pages']->find($uri_path);
        if ($page) {
            $theme = $this->grav['config']->get('system.pages.theme');
            $pages_to_404 = $this->grav['config']->get('themes.' . $theme . '.system.pages_to_404');
            $pages_to_redirect = $this->grav['config']->get('themes.' . $theme . '.system.pages_to_redirect');
            if (!empty($pages_to_404)) {
                if (in_array($page->rawRoute(), $pages_to_404)) {
                    $this->grav->redirect('/error');
                }
            }
        }
    }

    /**
     * Programmatically add a custom page.
     *
     * @param $url
     * @param $filename
     * @param null $parent
     * @param null $route
     * @return Page
     * @throws \Exception
     */
    public function addPage($url, $filename, $parent = null, $route = null): Page
    {
        /** @var Pages $pages */
        $pages = $this->grav['pages'];
        $page = $pages->dispatch($url);

        if (!$page) {
            $page = new Page;
            $pluginPage = new \SplFileInfo(__DIR__ . '/pages/' . $filename);
            if (!empty($pluginPage)) {
                $page->init(new \SplFileInfo(__DIR__ . '/pages/' . $filename));

                $route = $page->route();
                $page->rawRoute($url);
                $page->routeAliases([$url]);
                if ($parent) {
                    $page->parent($parent);
                } else {
                    $page->parent($pages->root());
                }
                $pages->addPage($page, $url);
                $pages->addPage($page, $route);
            }
        }
        return $page;
    }

    public function addPageFromTheme($url, $filename, $parent = null, $route = null): Page
    {
        /** @var Pages $pages */
        $pages = $this->grav['pages'];
        $page = $pages->dispatch($url);

        if (!$page) {
            $page = new Page;
            $themeFile = new \SplFileInfo($this->grav['locator']->findResource('theme://pages') . '/' . $filename);
            if (!empty($themeFile)) {
                $page->init($themeFile);

                $route = $page->route();
                $page->rawRoute($url);
                $page->routeAliases([$url]);
                if ($parent) {
                    $page->parent($parent);
                } else {
                    $page->parent($pages->root());
                }
                $pages->addPage($page, $url);
                $pages->addPage($page, $route);
            }
        }
        return $page;
    }

    /**
     * [onPagesInitialized]
     *
     * @return void
     * @throws \Exception
     */
    public function onPagesInitialized(): void
    {
        if ($this->isAdmin()) {
            $this->grav['admin']->enablePages();
        }

        /** @var Pages $pages */
        $pages = $this->grav['pages'];
        $this->addPage('/catalog', 'catalog/catalog.md');
        $this->addPage('/map', 'map/map.md');
        $this->addPage('/datasource', 'datasource/datasource.md');
        $this->addPage('/detail', 'detail/detail.md');
        $this->addPage('/help', 'help/help.md');
        $this->addPage('/measure', 'measure/measure.md');
        $this->addPage('/provider', 'provider/provider.md');
        $this->addPage('/rss', 'rss/news.md');
        $page = $this->addPage('/search', 'search/modular.md');
        $this->addPage('/search/_result', 'search/_result/result.md', $page);
        $this->addPage('/search/_search', 'search/_search/home-search.md', $page);
        $this->addPage('/sitemap', 'sitemap/sitemap.md');
        $this->addPage('/home/_categories', 'home/_categories/home-categories.md', $pages->find('/home'), '/home/_categories');
        $this->addPage('/home/_news', 'home/_news/home-news.md', $pages->find('/'), '/home/_news');
        $this->addPage('/home/_search', 'home/_search/home-search.md', $pages->find('/'), '/home/_search');
        $this->addPage('/home/_hits', 'home/_hits/home-hits.md', $pages->find('/'), '/home/_hits');
        $this->addPage('/contact/success', 'contact/success/contact-success.md', $pages->find('/contact'));
        $this->addPageFromTheme('/contact/form', 'contact/form/default.md', $pages->find('/contact'));
    }

}
