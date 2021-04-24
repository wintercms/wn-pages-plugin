<?php namespace Winter\Pages;

use Event;
use Backend;
use Winter\Pages\Classes\Controller;
use Winter\Pages\Classes\Page as StaticPage;
use Winter\Pages\Classes\Router;
use Winter\Pages\Classes\Snippet;
use Winter\Pages\Classes\SnippetManager;
use Cms\Classes\Theme;
use Cms\Classes\Controller as CmsController;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'winter.pages::lang.plugin.name',
            'description' => 'winter.pages::lang.plugin.description',
            'author'      => 'Winter CMS',
            'icon'        => 'icon-files-o',
            'homepage'    => 'https://github.com/wintercms/wn-pages-plugin',
            'replaces'    => ['RainLab.Pages' => '<= 1.4.3'],
        ];
    }

    public function registerComponents()
    {
        return [
            '\Winter\Pages\Components\ChildPages' => 'childPages',
            '\Winter\Pages\Components\StaticPage' => 'staticPage',
            '\Winter\Pages\Components\StaticMenu' => 'staticMenu',
            '\Winter\Pages\Components\StaticBreadcrumbs' => 'staticBreadcrumbs'
        ];
    }

    public function registerPermissions()
    {
        return [
            'winter.pages.manage_pages' => [
                'tab'   => 'winter.pages::lang.page.tab',
                'order' => 200,
                'label' => 'winter.pages::lang.page.manage_pages'
            ],
            'winter.pages.manage_menus' => [
                'tab'   => 'winter.pages::lang.page.tab',
                'order' => 200,
                'label' => 'winter.pages::lang.page.manage_menus'
                ],
            'winter.pages.manage_content' => [
                'tab'   => 'winter.pages::lang.page.tab',
                'order' => 200,
                'label' => 'winter.pages::lang.page.manage_content'
            ],
            'winter.pages.access_snippets' => [
                'tab'   => 'winter.pages::lang.page.tab',
                'order' => 200,
                'label' => 'winter.pages::lang.page.access_snippets'
            ]
        ];
    }

    public function registerNavigation()
    {
        return [
            'pages' => [
                'label'       => 'winter.pages::lang.plugin.name',
                'url'         => Backend::url('winter/pages'),
                'icon'        => 'icon-files-o',
                'iconSvg'     => 'plugins/winter/pages/assets/images/pages-icon.svg',
                'permissions' => ['winter.pages.*'],
                'order'       => 200,

                'sideMenu' => [
                    'pages' => [
                        'label'       => 'winter.pages::lang.page.menu_label',
                        'icon'        => 'icon-files-o',
                        'url'         => 'javascript:;',
                        'attributes'  => ['data-menu-item'=>'pages'],
                        'permissions' => ['winter.pages.manage_pages']
                    ],
                    'menus' => [
                        'label'       => 'winter.pages::lang.menu.menu_label',
                        'icon'        => 'icon-sitemap',
                        'url'         => 'javascript:;',
                        'attributes'  => ['data-menu-item'=>'menus'],
                        'permissions' => ['winter.pages.manage_menus']
                    ],
                    'content' => [
                        'label'       => 'winter.pages::lang.content.menu_label',
                        'icon'        => 'icon-file-text-o',
                        'url'         => 'javascript:;',
                        'attributes'  => ['data-menu-item'=>'content'],
                        'permissions' => ['winter.pages.manage_content']
                    ],
                    'snippets' => [
                        'label'       => 'winter.pages::lang.snippet.menu_label',
                        'icon'        => 'icon-newspaper-o',
                        'url'         => 'javascript:;',
                        'attributes'  => ['data-menu-item'=>'snippet'],
                        'permissions' => ['winter.pages.access_snippets']
                    ]
                ]
            ]
        ];
    }

    public function registerFormWidgets()
    {
        return [
            FormWidgets\PagePicker::class => 'staticpagepicker',
            FormWidgets\MenuPicker::class => 'staticmenupicker',
        ];
    }

    public function boot()
    {
        Event::listen('cms.router.beforeRoute', function($url) {
            return Controller::instance()->initCmsPage($url);
        });

        Event::listen('cms.page.beforeRenderPage', function($controller, $page) {
            /*
             * Before twig renders
             */
            $twig = $controller->getTwig();
            $loader = $controller->getLoader();
            Controller::instance()->injectPageTwig($page, $loader, $twig);

            /*
             * Get rendered content
             */
            $contents = Controller::instance()->getPageContents($page);
            if (strlen($contents)) {
                return $contents;
            }
        });

        Event::listen('cms.page.initComponents', function($controller, $page) {
            Controller::instance()->initPageComponents($controller, $page);
        });

        Event::listen('cms.block.render', function($blockName, $blockContents) {
            $page = CmsController::getController()->getPage();

            if (!isset($page->apiBag['staticPage'])) {
                return;
            }

            $contents = Controller::instance()->getPlaceholderContents($page, $blockName, $blockContents);
            if (strlen($contents)) {
                return $contents;
            }
        });

        Event::listen('pages.menuitem.listTypes', function() {
            return [
                'static-page'      => 'winter.pages::lang.menuitem.static_page',
                'all-static-pages' => 'winter.pages::lang.menuitem.all_static_pages'
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function($type) {
            if ($type == 'url') {
                return [];
            }

            if ($type == 'static-page'|| $type == 'all-static-pages') {
                return StaticPage::getMenuTypeInfo($type);
            }
        });

        Event::listen('pages.menuitem.resolveItem', function($type, $item, $url, $theme) {
            if ($type == 'static-page' || $type == 'all-static-pages') {
                return StaticPage::resolveMenuItem($item, $url, $theme);
            }
        });

        Event::listen('backend.form.extendFieldsBefore', function($formWidget) {
            if ($formWidget->model instanceof \Cms\Classes\Partial) {
                Snippet::extendPartialForm($formWidget);
            }
        });

        Event::listen('cms.template.save', function($controller, $template, $type) {
            Plugin::clearCache();
        });

        Event::listen('cms.template.processSettingsBeforeSave', function($controller, $dataHolder) {
            $dataHolder->settings = Snippet::processTemplateSettingsArray($dataHolder->settings);
        });

        Event::listen('cms.template.processSettingsAfterLoad', function($controller, $template) {
            Snippet::processTemplateSettings($template);
        });

        Event::listen('cms.template.processTwigContent', function($template, $dataHolder) {
            if ($template instanceof \Cms\Classes\Layout) {
                $dataHolder->content = Controller::instance()->parseSyntaxFields($dataHolder->content);
            }
        });

        Event::listen('backend.richeditor.listTypes', function () {
            return [
                'static-page' => 'winter.pages::lang.menuitem.static_page',
            ];
        });

        Event::listen('backend.richeditor.getTypeInfo', function ($type) {
            if ($type === 'static-page') {
                return StaticPage::getRichEditorTypeInfo($type);
            }
        });

        Event::listen('system.console.theme.sync.getAvailableModelClasses', function () {
            return [
                Classes\Menu::class,
                Classes\Page::class,
            ];
        });
    }

    /**
     * Register new Twig variables
     * @return array
     */
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'staticPage' => ['Winter\Pages\Classes\Page', 'url']
            ]
        ];
    }

    public static function clearCache()
    {
        $theme = Theme::getEditTheme();

        $router = new Router($theme);
        $router->clearCache();

        StaticPage::clearMenuCache($theme);
        SnippetManager::clearCache($theme);
    }
}
