<?php

namespace Winter\Pages\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Theme;
use Winter\Pages\Classes\MenuItemReference;
use Winter\Pages\Classes\Page as StaticPageClass;
use Winter\Pages\Classes\Router;

/**
 * The static breadcrumbs component.
 *
 * @package winter\pages
 * @author Alexey Bobkov, Samuel Georges
 */
class StaticBreadcrumbs extends ComponentBase
{
    /**
     * @var array An array of the Winter\Pages\Classes\MenuItemReference class.
     */
    public $breadcrumbs = [];

    public function componentDetails()
    {
        return [
            'name'        => 'winter.pages::lang.component.static_breadcrumbs_name',
            'description' => 'winter.pages::lang.component.static_breadcrumbs_description',
        ];
    }

    public function onRun()
    {
        $url = $this->getRouter()->getUrl();

        if (!strlen($url)) {
            $url = '/';
        }

        $theme = Theme::getActiveTheme();
        $router = new Router($theme);
        $page = $router->findByUrl($url);

        if ($page) {
            $tree = StaticPageClass::buildMenuTree($theme);

            $code = $startCode = $page->getBaseFileName();
            $breadcrumbs = [];

            while ($code) {
                if (!isset($tree[$code])) {
                    break;
                }

                $pageInfo = $tree[$code];

                if ($pageInfo['navigation_hidden']) {
                    $code = $pageInfo['parent'];
                    continue;
                }

                $reference = new MenuItemReference();
                $reference->title = $pageInfo['title'];
                $reference->url = StaticPageClass::url($code);
                $reference->isActive = $code == $startCode;

                $breadcrumbs[] = $reference;

                $code = $pageInfo['parent'];
            }

            $breadcrumbs = array_reverse($breadcrumbs);

            $this->breadcrumbs = $this->page['breadcrumbs'] = $breadcrumbs;
        }
    }
}
