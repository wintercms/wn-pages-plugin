<?php

use Winter\Storm\Support\ClassLoader;

/**
 * To allow compatibility with plugins that extend the original RainLab.Pages plugin, this will alias those classes to
 * use the new Winter.Pages classes.
 */
$aliases = [
    Winter\Pages\Plugin::class                       => 'RainLab\Pages\Plugin',
    Winter\Pages\Classes\Content::class              => 'RainLab\Pages\Classes\Content',
    Winter\Pages\Classes\Controller::class           => 'RainLab\Pages\Classes\Controller',
    Winter\Pages\Classes\Menu::class                 => 'RainLab\Pages\Classes\Menu',
    Winter\Pages\Classes\Page::class                 => 'RainLab\Pages\Classes\Page',
    Winter\Pages\Classes\Router::class               => 'RainLab\Pages\Classes\Router',
    Winter\Pages\Classes\Snippet::class              => 'RainLab\Pages\Classes\Snippet',
    Winter\Pages\Classes\SnippetManager::class       => 'RainLab\Pages\Classes\SnippetManager',
    Winter\Pages\Components\ChildPages::class        => 'RainLab\Pages\Components\ChildPages',
    Winter\Pages\Components\StaticBreadcrumbs::class => 'RainLab\Pages\Components\StaticBreadcrumbs',
    Winter\Pages\Components\StaticMenu::class        => 'RainLab\Pages\Components\StaticMenu',
    Winter\Pages\Components\StaticPage::class        => 'RainLab\Pages\Components\StaticPage',
    Winter\Pages\Controllers\Index::class            => 'RainLab\Pages\Controllers\Index',
    Winter\Pages\FormWidgets\MenuItems::class        => 'RainLab\Pages\FormWidgets\MenuItems',
    Winter\Pages\FormWidgets\MenuItemSearch::class   => 'RainLab\Pages\FormWidgets\MenuItemSearch',
    Winter\Pages\FormWidgets\PagePicker::class       => 'RainLab\Pages\FormWidgets\PagePicker',
    Winter\Pages\Widgets\MenuList::class             => 'RainLab\Pages\Widgets\MenuList',
    Winter\Pages\Widgets\PageList::class             => 'RainLab\Pages\Widgets\PageList',
    Winter\Pages\Widgets\SnippetList::class          => 'RainLab\Pages\Widgets\SnippetList',
];

app(ClassLoader::class)->addAliases($aliases);
