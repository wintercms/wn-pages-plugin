<?php

use Winter\Storm\Support\ClassLoader;

/**
 * To allow compatibility with plugins that extend the original RainLab.Pages plugin, this will alias those classes to
 * use the new Winter.Pages classes.
 */
$aliases = [
    Winter\Pages\Plugin::class                       => RainLab\Pages\Plugin::class,
    Winter\Pages\Classes\Content::class              => RainLab\Pages\Classes\Content::class,
    Winter\Pages\Classes\Controller::class           => RainLab\Pages\Classes\Controller::class,
    Winter\Pages\Classes\Menu::class                 => RainLab\Pages\Classes\Menu::class,
    Winter\Pages\Classes\Page::class                 => RainLab\Pages\Classes\Page::class,
    Winter\Pages\Classes\Router::class               => RainLab\Pages\Classes\Router::class,
    Winter\Pages\Classes\Snippet::class              => RainLab\Pages\Classes\Snippet::class,
    Winter\Pages\Classes\SnippetManager::class       => RainLab\Pages\Classes\SnippetManager::class,
    Winter\Pages\Components\ChildPages::class        => RainLab\Pages\Components\ChildPages::class,
    Winter\Pages\Components\StaticBreadcrumbs::class => RainLab\Pages\Components\StaticBreadcrumbs::class,
    Winter\Pages\Components\StaticMenu::class        => RainLab\Pages\Components\StaticMenu::class,
    Winter\Pages\Components\StaticPage::class        => RainLab\Pages\Components\StaticPage::class,
    Winter\Pages\Controllers\Index::class            => RainLab\Pages\Controllers\Index::class,
    Winter\Pages\FormWidgets\MenuItems::class        => RainLab\Pages\FormWidgets\MenuItems::class,
    Winter\Pages\FormWidgets\MenuItemSearch::class   => RainLab\Pages\FormWidgets\MenuItemSearch::class,
    Winter\Pages\FormWidgets\PagePicker::class       => RainLab\Pages\FormWidgets\PagePicker::class,
    Winter\Pages\Widgets\MenuList::class             => RainLab\Pages\Widgets\MenuList::class,
    Winter\Pages\Widgets\PageList::class             => RainLab\Pages\Widgets\PageList::class,
    Winter\Pages\Widgets\SnippetList::class          => RainLab\Pages\Widgets\SnippetList::class,
];

app(ClassLoader::class)->addAliases($aliases);
