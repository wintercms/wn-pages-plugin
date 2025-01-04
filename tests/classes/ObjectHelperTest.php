<?php

namespace Winter\Pages\Tests\Classes;

use Cms\Classes\Theme;
use PluginTestCase;
use Winter\Pages\Classes\ObjectHelper;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Support\Facades\Config;

class ObjectHelperTest extends PluginTestCase
{
    /**
     * Assert line ending conversion works and respects the cms config
     */
    public function testConvertLineEndings(): void
    {
        $sample = "Hello\r\nWorld\r\n";

        // Check line ending conversion enabled
        Config::set('cms.convertLineEndings', true);
        $this->assertEquals("Hello\nWorld\n", ObjectHelper::convertLineEndings($sample));

        // Check input returned without modification if converting is disabled
        Config::set('cms.convertLineEndings', false);
        $this->assertEquals($sample, ObjectHelper::convertLineEndings($sample));
    }

    /**
     * Assert that the key generated includes unique identifiers
     */
    public function testGetTypePreviewSessionCacheKey(): void
    {
        $sessionId = \Session::getId();
        $type = 'page-test';
        $alias = 'page-alias';

        $key = ObjectHelper::getTypePreviewSessionCacheKey($type, $alias);

        $this->assertStringContainsString($sessionId, $key);
        $this->assertStringContainsString($type, $key);
        $this->assertStringContainsString($alias, $key);
    }

    /**
     * Assert that resolving object type returns the correct class
     */
    public function testResolveTypeClassName(): void
    {
        // Test resolving valid types
        $this->assertEquals(\Winter\Pages\Classes\Page::class, ObjectHelper::resolveTypeClassName('page'));
        $this->assertEquals(\Winter\Pages\Classes\Menu::class, ObjectHelper::resolveTypeClassName('menu'));
        $this->assertEquals(\Winter\Pages\Classes\Content::class, ObjectHelper::resolveTypeClassName('content'));

        // Test exception handling for invalid type
        $this->expectException(ApplicationException::class);
        ObjectHelper::resolveTypeClassName('test');
    }

    /**
     * Assert that resolving class returns the correct object type
     */
    public function testResolveClassType(): void
    {
        // Test resolving valid types
        $this->assertEquals('page', ObjectHelper::resolveClassType(\Winter\Pages\Classes\Page::class));
        $this->assertEquals('menu', ObjectHelper::resolveClassType(\Winter\Pages\Classes\Menu::class));
        $this->assertEquals('content', ObjectHelper::resolveClassType(\Winter\Pages\Classes\Content::class));

        // Test exception handling for invalid type
        $this->expectException(ApplicationException::class);
        ObjectHelper::resolveClassType(\Winter\Pages\Classes\Missing::class);
    }

    /**
     * Assert that fill object returns the correct object with data
     */
    public function testFillObject(): void
    {
        $page = ObjectHelper::fillObject(Theme::getActiveTheme(), 'page', '', [
            'markup' => '<h1>hello world</h1>',
        ]);

        $this->assertInstanceOf(\Winter\Pages\Classes\Page::class, $page);
        $this->assertEquals($page->markup, '<h1>hello world</h1>');
    }

    /**
     * Assert that create object returns the correct object
     */
    public function testCreateObject(): void
    {
        $page = ObjectHelper::createObject(Theme::getActiveTheme(), 'page');

        $this->assertInstanceOf(\Winter\Pages\Classes\Page::class, $page);
    }
}
