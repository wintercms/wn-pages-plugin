<?php namespace Winter\Pages\Classes;

use ApplicationException;
use Cache;
use Carbon\Carbon;
use Cms\Classes\CmsException;
use Cms\Classes\Controller as CmsController;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use Cms\Classes\ComponentManager;
use Exception;
use Flash;
use Session;
use SystemException;
use Winter\Pages\Classes\SnippetManager;

class SnippetLoader
{
    protected static $pageSnippetsCache = [];

    /**
     * Add a component registered as a snippet to the active controller.
     *
     * @throws SystemException
     * @throws CmsException
     */
    public static function registerComponentSnippet(array $snippetInfo): string
    {
        $controller = CmsController::getController();

        // Make an unique alias for this snippet based on its name and parameters
        #$snippetInfo['code'] = uniqid($snippetInfo['code'] . '_' . md5(serialize($snippetInfo['properties'])) . '_');
        // the line above was commented out to allow the overriden partials in theme to be used for the component alias

        static::attachComponentSnippetToController($snippetInfo, $controller, true);
        static::cacheSnippet($snippetInfo['code'], $snippetInfo);

        return $snippetInfo['code'];
    }

    /**
     * Add a partial registered as a snippet to the active controller.
     *
     * @throws ApplicationException
     */
    public static function registerPartialSnippet(array $snippetInfo): string
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
     */
    public static function saveCachedSnippets(CmsPage $page): void
    {
        if (empty(static::$pageSnippetsCache)) {
            return;
        }

        Cache::put(
            static::getMapCacheKey($page),
            serialize(static::$pageSnippetsCache),
            Carbon::now()->addDay()
        );
    }

    /**
     * Register back to the current controller all component snippets previously saved.
     * This make AJAX handlers of these components available.
     */
    public static function restoreComponentSnippetsFromCache(CmsController $controller, CmsPage $page): void
    {
        $componentSnippets = static::fetchCachedSnippets($page);

        foreach ($componentSnippets as $componentInfo) {
            try {
                static::attachComponentSnippetToController($componentInfo, $controller);
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
     * @throws SystemException
     * @throws CmsException
     */
    protected static function attachComponentSnippetToController(array $componentInfo, CmsController $controller, bool $triggerRunEvents = false): void
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

        if ($triggerRunEvents) {
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
     */
    protected static function cacheSnippet(string $alias, array $snippetInfo): void
    {
        static::$pageSnippetsCache[$alias] = $snippetInfo;
    }

    /**
     * Get cached component snippets from the cache.
     */
    protected static function fetchCachedSnippets(CmsPage $page): array
    {
        try {
            $cache = unserialize(Cache::get(static::getMapCacheKey($page), serialize([])));
        } catch (Exception $e) {
            $error = $e->getMessage();
            trace_log($error);
            Flash::error($error);
        }

        return is_array($cache) ? $cache : [];
    }

    /**
     * Get a cache key for the current page and the current user.
     */
    protected static function getMapCacheKey(CmsPage $page): string
    {
        $theme = Theme::getActiveTheme();

        return 'dynamic-snippet-map-' . md5($theme->getPath() . $page['url'] . Session::getId());
    }
}
