<?php namespace StudioAzura\W2C\Classes;

use ApplicationException;
use Cache;
use Carbon\Carbon;
use Cms\Classes\CmsException;
use Cms\Classes\Controller as CmsController;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use Cms\Classes\ComponentManager;
use Session;
use SystemException;

use Winter\Pages\Classes\SnippetManager;

class SnippetLoader
{
    protected static $pageSnippetsCache = [];

    /**
     * Add a component registered as a snippet to the active controller.
     *
     * @param array $snippetInfo        The info of the snippet to register
     * @return string                   The generated unique alias for this snippet
     * @throws SystemException
     * @throws CmsException
     */
    public static function registerComponentSnippet($snippetInfo)
    {
        $controller = CmsController::getController();

        // Make an unique alias for this snippet based on its name and parameters
        #$snippetInfo['code'] = uniqid($snippetInfo['code'] . '_' . md5(serialize($snippetInfo['properties'])) . '_');
        // the line above was commented out to allow the overriden partials in theme to be used for the component alias

        self::attachComponentSnippetToController($snippetInfo, $controller, true);
        self::cacheSnippet($snippetInfo['code'], $snippetInfo);

        return $snippetInfo['code'];
    }

    /**
     * Add a partial registered as a snippet to the active controller.
     *
     * @param array $snippetInfo        The info of the snippet to register
     * @return string                   The generated unique alias for this snippet
     * @throws ApplicationException
     */
    public static function registerPartialSnippet($snippetInfo)
    {
        $theme = Theme::getActiveTheme();
        $partialSnippetMap = SnippetManager::instance()->getPartialSnippetMap($theme);
        $snippetCode = $snippetInfo['code'];

        if (!array_key_exists($snippetCode, $partialSnippetMap)) {
            throw new ApplicationException(sprintf('Partial for the snippet %s is not found', $snippetCode));
        }

        return $partialSnippetMap[$snippetCode];
    }

    /**
     * Save to the cache the component snippets loaded for this page.
     * Should be called once after all snippets are loaded to avoid multiple serializations.
     *
     * @param CmsPage $page             The CMS Page to which the cache should be attached
     */
    public static function saveCachedSnippets(CmsPage $page)
    {
        if (empty(self::$pageSnippetsCache)) {
            return;
        }

        Cache::put(
            self::getMapCacheKey($page),
            serialize(self::$pageSnippetsCache),
            Carbon::now()->addDay()
        );
    }

    /**
     * Register back to the current controller all component snippets previously saved.
     * This make AJAX handlers of these components available.
     *
     * @param CmsController $cmsController
     * @param CmsPage $page                 The CMS page for which to load the cache
     */
    public static function restoreComponentSnippetsFromCache($cmsController, CmsPage $page)
    {
        $componentSnippets = self::fetchCachedSnippets($page);

        foreach ($componentSnippets as $componentInfo) {
            try {
                self::attachComponentSnippetToController($componentInfo, $cmsController);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Attach a component-based snippet to a controller.
     *
     * Register the component if it is not defined yet.
     * This is required because not all component snippets are registered as components,
     * but it's safe to register them in render-time.
     *
     * If asked, the run lifecycle events of the component can be run. This is required for
     * component that are added late in the page execution like with the twig filter.
     *
     * @param array $componentInfo
     * @param CmsController $controller
     * @param bool $triggerRun              Should the run events of the component lifecycle be triggered?
     * @throws SystemException
     * @throws CmsException
     */
    protected static function attachComponentSnippetToController($componentInfo, CmsController $controller, $triggerRun = false)
    {
        $componentManager = ComponentManager::instance();

        if (!$componentManager->hasComponent($componentInfo['component'])) {
            $componentManager->registerComponent($componentInfo['component'], $componentInfo['code']);
        }

        $component = $controller->addComponent(
            $componentInfo['component'],
            $componentInfo['code'],
            $componentInfo['properties']
        );

        if ($triggerRun) {
            if ($component->fireEvent('component.beforeRun', [], true)) {
                return;
            }

            if ($component->onRun()) {
                return;
            }

            if ($component->fireEvent('component.run', [], true)) {
                return;
            }
        }
    }

    /**
     * Store a component snippet to the cache.
     * The cache is not actually saved; saveCachedSnippets() must be called to persist the cache.
     *
     * @param string $alias                     The unique alias of the snippet
     * @param array $snippetInfo        The info of the snippet
     */
    protected static function cacheSnippet($alias, $snippetInfo)
    {
        self::$pageSnippetsCache[$alias] = $snippetInfo;
    }

    /**
     * Get cached component snippets from the cache.
     *
     * @param CmsPage $page         The CMS page for which to load the cache
     */
    protected static function fetchCachedSnippets(CmsPage $page)
    {
        $cache = @unserialize(Cache::get(self::getMapCacheKey($page), serialize([])));

        return is_array($cache) ? $cache : [];
    }

    /**
     * Get a cache key for the current page and the current user.
     *
     * @param CmsPage $page         The CMS page for which to load the cache
     * @return string
     */
    protected static function getMapCacheKey(CmsPage $page)
    {
        $theme = Theme::getActiveTheme();

        return 'dynamic-snippet-map-' . md5($theme->getPath() . $page['url'] . Session::getId());
    }
}
