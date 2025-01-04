<?php

namespace Winter\Pages\Classes;

use Winter\Storm\Support\Facades\Event;

/**
 * Represents a menu item.
 * This class is used in the back-end for managing the menu items.
 * On the front-end items are represented with the
 * \Winter\Pages\Classes\MenuItemReference objects.
 *
 * @package winter\pages
 * @author Alexey Bobkov, Samuel Georges
 */
class MenuItem
{
    /**
     * @var string Specifies the menu title
     */
    public $title = '';

    /**
     * @var array Specifies the item subitems
     */
    public $items = [];

    /**
     * @var self|null Specifies the parent menu item.
     */
    public $parent = null;

    /**
     * @var boolean Determines whether the auto-generated menu items could have subitems.
     */
    public $nesting = false;

    /**
     * @var string Specifies the menu item type - URL, static page, etc.
     */
    public $type = 'url';

    /**
     * @var string|null Specifies the URL for URL-type items.
     */
    public $url = null;

    /**
     * @var string|null Specifies the menu item code.
     */
    public $code = null;

    /**
     * @var string|null Specifies the object identifier the item refers to.
     * The identifier could be the database identifier or an object code.
     */
    public $reference = null;

    /**
     * @var boolean Indicates that generated items should replace this item.
     */
    public $replace = false;

    /**
     * @var string|null Specifies the CMS page path to resolve dynamic menu items to.
     */
    public $cmsPage = null;

    /**
     * @var boolean Used by the system internally.
     */
    public $exists = false;

    public $fillable = [
        'title',
        'nesting',
        'type',
        'url',
        'code',
        'reference',
        'cmsPage',
        'replace',
        'viewBag',
    ];

    /**
     * @var array Contains the view bag properties.
     * This property is used by the menu editor internally.
     */
    public $viewBag = [];

    /**
     * Initializes an array of MenuItem objects from an array of arrays
     * containing menu item data.
     */
    public static function initFromArray(array $items): array
    {
        $result = [];

        foreach ($items as $itemData) {
            $obj = new self();

            foreach ($itemData as $name => $value) {
                if ($name != 'items') {
                    if (property_exists($obj, $name)) {
                        $obj->$name = $value;
                    }
                } else {
                    $obj->items = self::initFromArray($value);
                }
            }

            $result[] = $obj;
        }

        return $result;
    }

    /**
     * Returns a list of registered menu item types
     */
    public function getTypeOptions(): array
    {
        /*
         * Baked in types
         */
        $result = [
            'url' => 'URL',
            'header' => 'Header',
        ];

        $apiResult = Event::fire('pages.menuitem.listTypes');

        if (is_array($apiResult)) {
            foreach ($apiResult as $typeList) {
                if (!is_array($typeList)) {
                    continue;
                }

                foreach ($typeList as $typeCode => $typeName) {
                    $result[$typeCode] = $typeName;
                }
            }
        }

        return $result;
    }

    public function getCmsPageOptions()
    {
        return []; // CMS Pages are loaded client-side
    }

    public function getReferenceOptions()
    {
        return []; // References are loaded client-side
    }

    public static function getTypeInfo($type): array
    {
        $result = [];
        $apiResult = Event::fire('pages.menuitem.getTypeInfo', [$type]);

        if (is_array($apiResult)) {
            foreach ($apiResult as $typeInfo) {
                if (!is_array($typeInfo)) {
                    continue;
                }

                foreach ($typeInfo as $name => $value) {
                    if ($name == 'cmsPages') {
                        $cmsPages = [];

                        foreach ($value as $page) {
                            $baseName = $page->getBaseFileName();
                            $pos = strrpos($baseName, '/');

                            $dir = $pos !== false ? substr($baseName, 0, $pos) . ' / ' : null;
                            $cmsPages[$baseName] = strlen($page->title)
                                ? $dir . $page->title
                                : $baseName;
                        }

                        $value = $cmsPages;
                    }

                    $result[$name] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the menu item data to an array
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->fillable as $property) {
            $result[$property] = $this->$property;
        }

        return $result;
    }
}
