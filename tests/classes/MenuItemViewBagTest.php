<?php

namespace Winter\Pages\Tests\Classes;

use System\Tests\Bootstrap\PluginTestCase;
use Winter\Pages\Classes\MenuItemViewBag;

class MenuItemViewBagTest extends PluginTestCase
{
    public function testGetViewBagArrayWithDefaults()
    {
        $viewBag = new MenuItemViewBag();
        $this->assertEquals([
            'isHidden' => false,
            'isExternal' => false,
            'cssClass' => '',
        ], $viewBag->toArray());
    }

    public function testGetViewBagArrayWithEdits()
    {
        $viewBag = new MenuItemViewBag([
            'isHidden' => true,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ]);
        $this->assertEquals([
            'isHidden' => true,
            'isExternal' => false,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ], $viewBag->toArray());
    }

    public function testGetViewBagValuesAsArray()
    {
        $viewBag = new MenuItemViewBag([
            'isHidden' => true,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ]);

        $this->assertTrue($viewBag['isHidden']);
        $this->assertFalse($viewBag['isExternal']);
        $this->assertEquals('test-class', $viewBag['cssClass']);
        $this->assertEquals('nofollow', $viewBag['rel']);
    }

    public function testSetViewBagValuesAsArray()
    {
        $viewBag = new MenuItemViewBag([
            'isHidden' => true,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ]);

        $this->assertEquals('test-class', $viewBag['cssClass']);
        $viewBag['cssClass'] = 'overridden';
        $this->assertEquals('overridden', $viewBag['cssClass']);

        $viewBag['arrayValues'] = [
            'item 1' => 'value 1',
        ];

        $this->assertEquals([
            'item 1' => 'value 1',
        ], $viewBag['arrayValues']);

        $viewBag['arrayValues']['item 2'] = 'value 2';

        $this->assertEquals([
            'item 1' => 'value 1',
            'item 2' => 'value 2',
        ], $viewBag['arrayValues']);
    }

    public function testViewBagIsIterable()
    {
        $data = [];
        $viewBag = new MenuItemViewBag([
            'isHidden' => true,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ]);

        foreach ($viewBag as $key => $value) {
            $data[$key] = $value;
        }

        $this->assertEquals([
            'isHidden' => true,
            'isExternal' => false,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ], $data);
    }

    public function testCreatingViewBagFromAnotherViewBag()
    {
        $viewBag = new MenuItemViewBag([
            'isHidden' => true,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ]);

        // Should be cloned, but not a reference.
        $viewBag2 = new MenuItemViewBag($viewBag);

        $this->assertEquals([
            'isHidden' => true,
            'isExternal' => false,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ], $viewBag2->toArray());

        $viewBag2['isHidden'] = false;

        $this->assertTrue($viewBag['isHidden']);
        $this->assertFalse($viewBag2['isHidden']);
    }

    public function testViewBagIsSerializableAndUnserializable()
    {
        $viewBag = new MenuItemViewBag([
            'isHidden' => true,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ]);

        $expected = 'O:36:"Winter\Pages\Classes\MenuItemViewBag":4:{s:8:"isHidden";b:1;s:10:"isExternal";b:0;s:8:"cssClass";s:10:"test-class";s:3:"rel";s:8:"nofollow";}';
        $serialized = serialize($viewBag);

        $this->assertEquals($expected, $serialized);

        $unserialized = unserialize($serialized);

        $this->assertEquals($viewBag, $unserialized);
    }

    public function testViewBagCanBeConvertedToString()
    {
        $viewBag = new MenuItemViewBag([
            'isHidden' => true,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ]);

        $expected = json_encode([
            'isHidden' => true,
            'isExternal' => false,
            'cssClass' => 'test-class',
            'rel' => 'nofollow',
        ]);

        $this->assertEquals($expected, (string) $viewBag);
    }
}
