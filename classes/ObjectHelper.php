<?php

namespace Winter\Pages\Classes;

use Cms\Classes\CmsCompoundObject;
use Cms\Classes\CmsObject;
use Cms\Classes\Theme;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Session;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Support\Facades\Config;

class ObjectHelper
{
    /**
     * @var array
     */
    protected static $types = [
        'page'    => \Winter\Pages\Classes\Page::class,
        'menu'    => \Winter\Pages\Classes\Menu::class,
        'content' => \Winter\Pages\Classes\Content::class,
    ];

    /**
     * Get the cache key for the preview data tied to session.
     */
    public static function getTypePreviewSessionCacheKey(string $type, string $alias): string
    {
        return "winter.pages.$type.preview:$alias" . Session::getId();
    }

    /**
     * Resolves an object's class name from the provided type
     * @throws ApplicationException if the provided type couldn't be resolved
     */
    public static function resolveTypeClassName(string $type): string
    {
        $types = static::$types;

        if (!array_key_exists($type, $types)) {
            throw new ApplicationException(Lang::get('winter.pages::lang.object.invalid_type') . ' - type - ' . $type);
        }

        return $types[$type];
    }

    /**
     * Resolves an object's type string from the provided class name
     * @throws ApplicationException if the provided class couldn't be resolved
     */
    public static function resolveClassType(string $class): string
    {
        $classes = array_flip(static::$types);

        if (!array_key_exists($class, $classes)) {
            throw new ApplicationException(Lang::get('winter.pages::lang.object.invalid_type') . ' - class - ' . $class);
        }

        return $classes[$class];
    }

    /**
     * Converts line endings based on the system's configuration
     */
    public static function convertLineEndings(?string $markup): string
    {
        if (Config::get('cms.convertLineEndings', false) === true) {
            $markup = str_replace("\r\n", "\n", $markup);
            $markup = str_replace("\r", "\n", $markup);
        }

        return $markup ?? '';
    }

    /**
     * Creates an object in the provided theme
     * @throws ApplicationException if the object cannot be created in the theme
     */
    public static function createObject(Theme $theme, string $type): CmsObject
    {
        $class = static::resolveTypeClassName($type);

        if (!($object = $class::inTheme($theme))) {
            throw new ApplicationException(Lang::get('winter.pages::lang.object.not_found'));
        }

        return $object;
    }

    /**
     * Gets the object by its type and path
     * @throws ApplicationException if the object cannot be resolved and $ignoreNotFound is true
     */
    public static function loadObject(Theme $theme, string $type, string $path, bool $ignoreNotFound = false): ?CmsObject
    {
        $class = static::resolveTypeClassName($type);

        if (!($object = call_user_func(array($class, 'load'), $theme, $path))) {
            if (!$ignoreNotFound) {
                throw new ApplicationException(Lang::get('winter.pages::lang.object.not_found'));
            }

            return null;
        }

        return $object;
    }

    /**
     * Fills the provided Winter.Pages object with the provided data
     */
    public static function fillObject(Theme $theme, string $type, string $path, array $data, ?CmsObject $object = null): CmsObject
    {
        $objectData = [];

        // Get the object to fill
        if (is_null($object)) {
            $path = trim($path);
            $object = !empty($path)
                ? static::loadObject($theme, $type, $path)
                : static::createObject($theme, $type);
        }

        // Set page layout super early because it cascades to other elements
        if ($type === 'page' && ($layout = $data['viewBag']['layout'] ?? null)) {
            $object->getViewBag()->setProperty('layout', $layout);
        }

        // Store viewBag data in the object's "settings" property
        if ($viewBag = array_get($data, 'viewBag')) {
            $objectData['settings'] = ['viewBag' => $viewBag];
        }

        // Store allowed properties on the object
        $fields = ['markup', 'code', 'fileName', 'content', 'itemData', 'name'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $objectData[$field] = $data[$field];
            }
        }

        switch ($type) {
            case 'page':
                // Populate the parentFileName for page objects
                $object->parentFileName = $data['parentFileName'] ?? null;

                $placeholders = array_get($data, 'placeholders');
                if (is_array($placeholders)) {
                    $placeholders = array_map([static::class, 'convertLineEndings'], $placeholders);
                }

                $objectData['placeholders'] = $placeholders;
                break;

            case 'content':
                $fileName = $objectData['fileName'];

                if (dirname($fileName) == 'static-pages') {
                    throw new ApplicationException(Lang::get('winter.pages::lang.content.cant_save_to_dir'));
                }

                $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                if ($extension === 'htm' || $extension === 'html' || !strlen($extension)) {
                    $objectData['markup'] = array_get($data, 'markup_html');
                }
                break;

            case 'menu':
                // If no item data is sent through POST, this means the menu is empty
                if (!isset($objectData['itemData'])) {
                    $objectData['itemData'] = [];
                } else {
                    $objectData['itemData'] = json_decode($objectData['itemData'], true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($objectData['itemData'])) {
                        $objectData['itemData'] = [];
                    }
                }
                break;
            default:
                break;
        }

        if (!empty($objectData['markup'])) {
            $objectData['markup'] = static::convertLineEndings($objectData['markup']);
        }

        if (!empty($data['objectForceSave']) && $object->mtime) {
            if ($data['objectMtime'] != $object->mtime) {
                throw new ApplicationException('mtime-mismatch');
            }
        }

        $object->fill($objectData);

        /*
         * Rehydrate the object viewBag array property where values are sourced.
         */
        if ($object instanceof CmsCompoundObject && is_array($viewBag)) {
            $object->viewBag = $viewBag + $object->viewBag;
        }

        return $object;
    }
}
