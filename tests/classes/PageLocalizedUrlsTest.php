<?php

namespace Winter\Pages\Tests\Classes;

use Cms\Classes\Theme;
use PluginTestCase;
use ReflectionProperty;
use Winter\Pages\Classes\Page;
use Winter\Translate\Models\Locale;

/**
 * Winter.Translate rewrites a static page's viewBag.url to the active locale's
 * URL when the page is fetched, so the menu tree's 'url' is only the default
 * locale's URL on a request in the default locale. A locale without a URL of
 * its own must still be linked under the default one, whatever the request.
 */
class PageLocalizedUrlsTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists(Locale::class)) {
            $this->markTestSkipped('Winter.Translate is not installed.');
        }

        $this->setStatic(Locale::class, 'cacheListEnabled', ['en' => 'English', 'fr' => 'Français']);
    }

    public function tearDown(): void
    {
        if (class_exists(Locale::class)) {
            $this->setStatic(Locale::class, 'cacheListEnabled', null);
        }
        $this->setStatic(Page::class, 'menuTreeCache', null);

        parent::tearDown();
    }

    public function testLocaleWithoutItsOwnUrlUsesTheDefaultUrl(): void
    {
        // the tree as built on a request in "fr"
        $this->withTree([
            'url' => '/conseils',
            'defaultUrl' => '/tips',
            'localeUrls' => ['fr' => '/conseils'],
        ]);

        $links = $this->alternateLinks();

        $this->assertStringEndsWith('/tips', $links['en']);
        $this->assertStringEndsWith('/conseils', $links['fr']);
    }

    public function testTreeCachedWithoutDefaultUrlFallsBackToUrl(): void
    {
        $this->withTree([
            'url' => '/tips',
            'localeUrls' => ['fr' => '/conseils'],
        ]);

        $this->assertStringEndsWith('/tips', $this->alternateLinks()['en']);
    }

    protected function withTree(array $page): void
    {
        $this->setStatic(Page::class, 'menuTreeCache', [
            '--root-pages--' => ['tips'],
            'tips' => $page + [
                'title' => 'Tips',
                'mtime' => 0,
                'items' => [],
                'parent' => null,
                'navigation_hidden' => false,
            ],
        ]);
    }

    protected function alternateLinks(): array
    {
        $item = (object) ['type' => 'static-page', 'reference' => 'tips', 'nesting' => false];

        return Page::resolveMenuItem($item, '/', new Theme())['alternateLinks'];
    }

    protected function setStatic(string $class, string $property, $value): void
    {
        $reflection = new ReflectionProperty($class, $property);
        $reflection->setAccessible(true);
        $reflection->setValue(null, $value);
    }
}
