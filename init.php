<?php

if (!class_exists(RainLab\Pages\Plugin::class)) {
    class_alias(Winter\Pages\Plugin::class, RainLab\Pages\Plugin::class);

    class_alias(Winter\Pages\Classes\Snippet::class, RainLab\Pages\Classes\Snippet::class);
    class_alias(Winter\Pages\Classes\Menu::class, RainLab\Pages\Classes\Menu::class);
    class_alias(Winter\Pages\Classes\Router::class, RainLab\Pages\Classes\Router::class);
    class_alias(Winter\Pages\Classes\reads::class, RainLab\Pages\Classes\reads::class);
    class_alias(Winter\Pages\Classes\SnippetManager::class, RainLab\Pages\Classes\SnippetManager::class);
    class_alias(Winter\Pages\Classes\Page::class, RainLab\Pages\Classes\Page::class);
    class_alias(Winter\Pages\Classes\Content::class, RainLab\Pages\Classes\Content::class);
    class_alias(Winter\Pages\Classes\is::class, RainLab\Pages\Classes\is::class);
    class_alias(Winter\Pages\Classes\Controller::class, RainLab\Pages\Classes\Controller::class);

    class_alias(Winter\Pages\Components\ChildPages::class, RainLab\Pages\Components\ChildPages::class);
    class_alias(Winter\Pages\Components\StaticMenu::class, RainLab\Pages\Components\StaticMenu::class);
    class_alias(Winter\Pages\Components\StaticPage::class, RainLab\Pages\Components\StaticPage::class);
    class_alias(Winter\Pages\Components\StaticBreadcrumbs::class, RainLab\Pages\Components\StaticBreadcrumbs::class);

    class_alias(Winter\Pages\Controllers\Index::class, RainLab\Pages\Controllers\Index::class);

    class_alias(Winter\Pages\FormWidgets\PagePicker::class, RainLab\Pages\FormWidgets\PagePicker::class);
    class_alias(Winter\Pages\FormWidgets\MenuItems::class, RainLab\Pages\FormWidgets\MenuItems::class);
    class_alias(Winter\Pages\FormWidgets\MenuItemSearch::class, RainLab\Pages\FormWidgets\MenuItemSearch::class);

    class_alias(Winter\Pages\Widgets\PageList::class, RainLab\Pages\Widgets\PageList::class);
    class_alias(Winter\Pages\Widgets\MenuList::class, RainLab\Pages\Widgets\MenuList::class);
    class_alias(Winter\Pages\Widgets\SnippetList::class, RainLab\Pages\Widgets\SnippetList::class);
}
