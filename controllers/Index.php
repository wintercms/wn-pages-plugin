<?php namespace Winter\Pages\Controllers;

use ApplicationException;
use Backend\Classes\Controller;
use BackendMenu;
use Cms\Classes\CmsObject;
use Cms\Classes\Theme;
use Cms\Widgets\TemplateList;
use Config;
use Cache;
use Event;
use Exception;
use Flash;
use Lang;
use Request;
use System\Helpers\DateTime;
use Url;
use Winter\Pages\Classes\Content;
use Winter\Pages\Classes\MenuItem;
use Winter\Pages\Classes\Page as StaticPage;
use Winter\Pages\Classes\SnippetManager;
use Winter\Pages\Plugin as PagesPlugin;
use Winter\Pages\Widgets\MenuList;
use Winter\Pages\Widgets\PageList;
use Winter\Pages\Widgets\SnippetList;

use Winter\Pages\Classes\ObjectHelper;

/**
 * Pages and Menus index
 *
 * @package winter\pages
 * @author Alexey Bobkov, Samuel Georges
 */
class Index extends Controller
{
    use \Backend\Traits\InspectableContainer;

    protected $theme;

    public $requiredPermissions = ['winter.pages.*'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        try {
            if (!($this->theme = Theme::getEditTheme())) {
                throw new ApplicationException(Lang::get('cms::lang.theme.edit.not_found'));
            }

            if ($this->user) {
                if ($this->user->hasAccess('winter.pages.manage_pages')) {
                    new PageList($this, 'pageList');
                    $this->vars['activeWidgets'][] = 'pageList';
                }

                if ($this->user->hasAccess('winter.pages.manage_menus')) {
                    new MenuList($this, 'menuList');
                    $this->vars['activeWidgets'][] = 'menuList';
                }

                if ($this->user->hasAccess('winter.pages.manage_content')) {
                    new TemplateList($this, 'contentList', function() {
                        return $this->getContentTemplateList();
                    });
                    $this->vars['activeWidgets'][] = 'contentList';
                }

                if ($this->user->hasAccess('winter.pages.access_snippets')) {
                    new SnippetList($this, 'snippetList');
                    $this->vars['activeWidgets'][] = 'snippetList';
                }
            }
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }

        $context = [
            'pageList' => 'pages',
            'menuList' => 'menus',
            'contentList' => 'content',
            'snippetList' => 'snippets',
        ];

        BackendMenu::setContext('Winter.Pages', 'pages', @$context[$this->vars['activeWidgets'][0]]);
    }

    //
    // Helpers
    //

    /**
     * Gets the object type for the current request
     * @throws ApplicationException if the current user does not have permissions to manage the identified type
     */
    protected function getObjectType(): string
    {
        $type = Request::input('objectType');

        $allowed = false;
        if ($type === 'content') {
            $allowed = $this->user->hasAccess('winter.pages.manage_content');
        } else {
            $allowed = $this->user->hasAccess("winter.pages.manage_{$type}s");
        }

        if (!$allowed) {
            throw new ApplicationException(Lang::get('winter.pages::lang.object.unauthorized_type', ['type' => $type]));
        }

        return $type;
    }

    /**
     * Gets the object from the current request
     * @throws ApplicationException if the current user does not have permissions to manage the identified type
     */
    public function getObjectFromRequest()
    {
        $type = $this->getObjectType();
        return ObjectHelper::fillObject(
            $this->theme,
            $type,
            Request::input('objectPath'),
            post()
        );
    }

    //
    // Pages, menus and text blocks
    //

    /**
     * Hook into the controller after the page action has executed (widgets initialized)
     * but before AJAX handlers are run
     * @TODO: Generalize the preview logic so it can be used in other plugins
     */
    public function pageAction()
    {
        $result = parent::pageAction();

        $formAlias = post('formWidgetAlias');

        if (!empty($formAlias) && isset($this->widget->{$formAlias})) {
            $widget = $this->widget->{$formAlias};

            $widget->bindEvent('form.refreshFields', function ($allFields) use ($widget) {
                $this->validateRequestTheme();

                $object = ObjectHelper::fillObject(
                    $this->theme,
                    $this->getObjectType(),
                    Request::input('objectPath'),
                    post()
                );

                Cache::put(
                    ObjectHelper::getTypePreviewSessionCacheKey($this->getObjectType(), $widget->alias),
                    $object->toArray()
                );
            });
        }

        return $result;
    }

    public function index()
    {
        $this->addJs('/modules/backend/assets/js/winter.treeview.js', 'core');
        $this->addJs('/plugins/winter/pages/assets/js/pages-page.js', 'Winter.Pages');
        $this->addJs('/plugins/winter/pages/assets/js/pages-snippets.js', 'Winter.Pages');
        $this->addCss('/plugins/winter/pages/assets/css/pages.css', 'Winter.Pages');

        // Preload the code editor class as it could be needed
        // before it loads dynamically.
        $this->addJs('/modules/backend/formwidgets/codeeditor/assets/js/build-min.js', 'core');

        $this->bodyClass = 'compact-container';
        $this->pageTitle = 'winter.pages::lang.plugin.name';
        $this->pageTitleTemplate = Lang::get('winter.pages::lang.page.template_title');

        if (Request::ajax() && Request::input('formWidgetAlias')) {
            $this->bindFormWidgetToController();
        }
    }

    public function index_onOpen()
    {
        $this->validateRequestTheme();

        $type = Request::input('type');
        $object = ObjectHelper::loadObject($this->theme, $type, Request::input('path'));

        return $this->pushObjectForm($type, $object);
    }

    public function onSave()
    {
        $this->validateRequestTheme();
        $type = $this->getObjectType();

        $object = ObjectHelper::fillObject(
            $this->theme,
            $type,
            Request::input('objectPath'),
            post()
        );
        $object->save();

        /*
         * Extensibility
         */
        Event::fire('pages.object.save', [$this, $object, $type]);
        $this->fireEvent('object.save', [$object, $type]);

        $result = $this->getUpdateResponse($object, $type);

        $successMessages = [
            'page' => 'winter.pages::lang.page.saved',
            'menu' => 'winter.pages::lang.menu.saved',
            'content' => 'winter.pages::lang.content.saved',
        ];

        $successMessage = isset($successMessages[$type])
            ? $successMessages[$type]
            : $successMessages['page'];

        Flash::success(Lang::get($successMessage));

        return $result;
    }

    public function onCreateObject()
    {
        $this->validateRequestTheme();

        $type = Request::input('type');
        $object = ObjectHelper::createObject($this->theme, $type);
        $parent = Request::input('parent');
        $parentPage = null;

        if ($type == 'page') {
            if (strlen($parent)) {
                $parentPage = StaticPage::load($this->theme, $parent);
            }

            $object->setDefaultLayout($parentPage);
        }

        $widget = $this->makeObjectFormWidget($type, $object);
        $widget->bindToController();
        $this->vars['objectPath'] = '';
        $this->vars['canCommit'] = $this->canCommitObject($object);
        $this->vars['canReset'] = $this->canResetObject($object);

        $result = [
            'tabTitle' => $this->getTabTitle($type, $object),
            'tab'      => $this->makePartial('form_page', [
                'form'         => $widget,
                'objectType'   => $type,
                'objectTheme'  => $this->theme->getDirName(),
                'objectMtime'  => null,
                'objectParent' => $parent,
                'parentPage'   => $parentPage
            ])
        ];

        return $result;
    }

    public function onDelete()
    {
        $this->validateRequestTheme();

        $deletedObjects = ObjectHelper::loadObject(
            $this->theme,
            $this->getObjectType(),
            trim(Request::input('objectPath', ''))
        )->delete();

        $result = [
            'deletedObjects' => $deletedObjects,
            'theme' => $this->theme->getDirName()
        ];

        return $result;
    }

    public function onDeleteObjects()
    {
        $this->validateRequestTheme();

        $type = Request::input('type');
        $objects = Request::input('object');

        if (!$objects) {
            $objects = Request::input('template');
        }

        $error = null;
        $deleted = [];

        try {
            foreach ($objects as $path => $selected) {
                if (!$selected) {
                    continue;
                }
                $object = ObjectHelper::loadObject($this->theme, $type, $path, true);
                if (!$object) {
                    continue;
                }

                $deletedObjects = $object->delete();
                if (is_array($deletedObjects)) {
                    $deleted = array_merge($deleted, $deletedObjects);
                }
                else {
                    $deleted[] = $path;
                }
            }
        }
        catch (Exception $ex) {
            $error = $ex->getMessage();
        }

        return [
            'deleted' => $deleted,
            'error'   => $error,
            'theme'   => Request::input('theme')
        ];
    }

    public function onOpenConcurrencyResolveForm()
    {
        return $this->makePartial('concurrency_resolve_form');
    }

    public function onGetMenuItemTypeInfo()
    {
        $type = Request::input('type');

        return [
            'menuItemTypeInfo' => MenuItem::getTypeInfo($type)
        ];
    }

    public function onUpdatePageLayout()
    {
        $this->validateRequestTheme();

        $type = $this->getObjectType();
        $object = ObjectHelper::fillObject(
            $this->theme,
            $type,
            Request::input('objectPath'),
            post()
        );

        return $this->pushObjectForm($type, $object, Request::input('formWidgetAlias'));
    }

    public function onGetInspectorConfiguration()
    {
        $configuration = [];

        $snippetCode = Request::input('snippet');
        $componentClass = Request::input('component');

        if (strlen($snippetCode)) {
            $snippet = SnippetManager::instance()->findByCodeOrComponent($this->theme, $snippetCode, $componentClass);
            if (!$snippet) {
                throw new ApplicationException(Lang::get('winter.pages::lang.snippet.not_found', ['code' => $snippetCode]));
            }

            $configuration = $snippet->getProperties();
        }

        return [
            'configuration' => [
                'properties'  => $configuration,
                'title'       => $snippet->getName(),
                'description' => $snippet->getDescription(),
            ],
        ];
    }

    public function onGetSnippetNames()
    {
        $codes = array_unique(Request::input('codes'));
        $result = [];

        foreach ($codes as $snippetCode) {
            $parts = explode('|', $snippetCode);
            $componentClass = null;

            if (count($parts) > 1) {
                $snippetCode = $parts[0];
                $componentClass = $parts[1];
            }

            $snippet = SnippetManager::instance()->findByCodeOrComponent($this->theme, $snippetCode, $componentClass);

            if (!$snippet) {
                $result[$snippetCode] = Lang::get('winter.pages::lang.snippet.not_found', ['code' => $snippetCode]);
            }
            else {
                $result[$snippetCode] =$snippet->getName();
            }
        }

        return [
            'names' => $result,
        ];
    }

    public function onMenuItemReferenceSearch()
    {
        $alias = Request::input('alias');

        $widget = $this->makeFormWidget(
            'Winter\Pages\FormWidgets\MenuItemSearch',
            [],
            ['alias' => $alias]
        );

        return $widget->onSearch();
    }

    /**
     * Commits the DB changes of a object to the filesystem
     *
     * @return array $response
     */
    public function onCommit()
    {
        $this->validateRequestTheme();
        $type = $this->getObjectType();
        $object = ObjectHelper::loadObject($this->theme, $type, trim(Request::input('objectPath', '')));

        if ($this->canCommitObject($object)) {
            // Populate the filesystem with the object and then remove it from the db
            $datasource = $this->getThemeDatasource();
            $datasource->pushToSource($object, 'filesystem');
            $datasource->removeFromSource($object, 'database');

            Flash::success(Lang::get('cms::lang.editor.commit_success', ['type' => $type]));
        }

        return array_merge($this->getUpdateResponse($object, $type), ['forceReload' => true]);
    }

    /**
     * Resets a object to the version on the filesystem
     *
     * @return array $response
     */
    public function onReset()
    {
        $this->validateRequestTheme();
        $type = $this->getObjectType();
        $object = ObjectHelper::loadObject($this->theme, $type, trim(Request::input('objectPath', '')));

        if ($this->canResetObject($object)) {
            // Remove the object from the DB
            $datasource = $this->getThemeDatasource();
            $datasource->removeFromSource($object, 'database');

            Flash::success(Lang::get('cms::lang.editor.reset_success', ['type' => $type]));
        }

        return array_merge($this->getUpdateResponse($object, $type), ['forceReload' => true]);
    }

    //
    // Methods for internal use
    //

    /**
     * Get the response to return in an AJAX request that updates an object
     *
     * @param CmsObject $object The object that has been affected
     * @param string $type The type of object being affected
     * @return array $result;
     */
    protected function getUpdateResponse(CmsObject $object, string $type)
    {
        $result = [
            'objectPath'  => $type != 'content' ? $object->getBaseFileName() : $object->fileName,
            'objectMtime' => $object->mtime,
            'tabTitle'    => $this->getTabTitle($type, $object)
        ];

        if ($type == 'page') {
            $result['pageUrl'] = Url::to($object->getViewBag()->property('url'));
            PagesPlugin::clearCache();
        }

        $result['canCommit'] = $this->canCommitObject($object);
        $result['canReset'] = $this->canResetObject($object);

        return $result;
    }

    /**
     * Get the active theme's datasource
     *
     * @return \Winter\Storm\Halcyon\Datasource\DatasourceInterface
     */
    protected function getThemeDatasource()
    {
        return $this->theme->getDatasource();
    }

    /**
     * Check to see if the provided object can be committed
     * Only available in debug mode, the DB layer must be enabled, and the object must exist in the database
     *
     * @param CmsObject $object
     * @return boolean
     */
    protected function canCommitObject(CmsObject $object)
    {
        $result = false;

        if (Config::get('app.debug', false) &&
            Theme::databaseLayerEnabled() &&
            $this->getThemeDatasource()->sourceHasModel('database', $object)
        ) {
            $result = true;
        }

        return $result;
    }

    /**
     * Check to see if the provided object can be reset
     * Only available when the DB layer is enabled and the object exists in both the DB & Filesystem
     */
    protected function canResetObject(CmsObject $object): bool
    {
        $result = false;

        if (Theme::databaseLayerEnabled()) {
            $datasource = $this->getThemeDatasource();
            $result = $datasource->sourceHasModel('database', $object) && $datasource->sourceHasModel('filesystem', $object);
        }

        return $result;
    }

    /**
     * Validates that the theme provided in the AJAX request matches the current one
     * @throws ApplicationException if the validation fails
     */
    protected function validateRequestTheme(): void
    {
        if ($this->theme->getDirName() != Request::input('theme')) {
            throw new ApplicationException(Lang::get('cms::lang.theme.edit.not_match'));
        }
    }

    protected function makeObjectFormWidget($type, $object, $alias = null)
    {
        $formConfigs = [
            'page'    => '~/plugins/winter/pages/classes/page/fields.yaml',
            'menu'    => '~/plugins/winter/pages/classes/menu/fields.yaml',
            'content' => '~/plugins/winter/pages/classes/content/fields.yaml'
        ];

        if (!array_key_exists($type, $formConfigs)) {
            throw new ApplicationException(Lang::get('winter.pages::lang.object.not_found'));
        }

        $widgetConfig = $this->makeConfig($formConfigs[$type]);
        $widgetConfig->model = $object;
        $widgetConfig->alias = $alias ?: 'form' . studly_case($type) . md5($object->exists ? $object->getFileName() : uniqid());
        $widgetConfig->context = !$object->exists ? 'create' : 'update';

        $widget = $this->makeWidget('Backend\Widgets\Form', $widgetConfig);

        if ($type == 'page') {
            $widget->bindEvent('form.extendFieldsBefore', function () use ($widget, $object) {
                $this->checkContentField($widget, $object);
                $this->addPagePlaceholders($widget, $object);
                $this->addPageSyntaxFields($widget, $object);
            });
        }

        return $widget;
    }

    protected function checkContentField($formWidget, $page)
    {
        if (!($layout = $page->getLayoutObject())) {
            return;
        }

        $component = $layout->getComponent('staticPage');

        if (!$component) {
            return;
        }

        if (!$component->property('useContent', true)) {
            unset($formWidget->secondaryTabs['fields']['markup']);
        }
    }

    /**
     * addPageSyntaxFields adds syntax defined fields to the form
     */
    protected function addPageSyntaxFields($formWidget, $page)
    {
        $fields = $page->listLayoutSyntaxFields();

        foreach ($fields as $fieldCode => $fieldConfig) {
            if ($fieldConfig['type'] === 'fileupload') {
                continue;
            }

            if ($fieldConfig['type'] == 'repeater') {
                if (empty($fieldConfig['form']) || !is_string($fieldConfig['form'])) {
                    $fieldConfig['form']['fields'] = array_get($fieldConfig, 'fields', []);
                    unset($fieldConfig['fields']);
                }
            }

            /*
             * Custom fields placement
             */
            $placement = !empty($fieldConfig['placement']) ? $fieldConfig['placement'] : null;

            switch ($placement) {
                case 'primary':
                    $formWidget->tabs['fields']['viewBag[' . $fieldCode . ']'] = $fieldConfig;
                    break;

                default:
                    $fieldConfig['cssClass'] = 'secondary-tab ' . array_get($fieldConfig, 'cssClass', '');
                    $formWidget->secondaryTabs['fields']['viewBag[' . $fieldCode . ']'] = $fieldConfig;
                    break;
            }

            /*
             * Translation support
             */
            $translatableTypes = ['text', 'textarea', 'richeditor', 'repeater', 'markdown', 'mediafinder'];
            if (in_array($fieldConfig['type'], $translatableTypes) && array_get($fieldConfig, 'translatable', true)) {
                $page->translatable[] = 'viewBag['.$fieldCode.']';
            }
        }
    }

    protected function addPagePlaceholders($formWidget, $page)
    {
        $placeholders = $page->listLayoutPlaceholders();

        foreach ($placeholders as $placeholderCode => $info) {
            if ($info['ignore']) {
                continue;
            }

            $placeholderTitle = $info['title'];
            $fieldConfig = [
                'tab'     => $placeholderTitle,
                'stretch' => '1',
                'size'    => 'huge',
            ];

            if ($info['type'] != 'text') {
                $fieldConfig['type'] = 'richeditor';
            }
            else {
                $fieldConfig['type'] = 'codeeditor';
                $fieldConfig['language'] = 'text';
                $fieldConfig['theme'] = 'chrome';
                $fieldConfig['showGutter'] = false;
                $fieldConfig['highlightActiveLine'] = false;
                $fieldConfig['cssClass'] = 'pagesTextEditor';
                $fieldConfig['showInvisibles'] = false;
                $fieldConfig['fontSize'] = 13;
                $fieldConfig['margin'] = '20';
            }

            $formWidget->secondaryTabs['fields']['placeholders['.$placeholderCode.']'] = $fieldConfig;

            /*
             * Translation support
             */
            $page->translatable[] = 'placeholders['.$placeholderCode.']';
        }
    }

    /**
     * Get the tab title the provided type & object
     */
    protected function getTabTitle(string $type, CmsObject $object): string
    {
        $result = '';
        switch ($type) {
            case 'page':
                $viewBag = $object->getViewBag();
                $result = $viewBag ? $viewBag->property('title') : false;
                if (!$result) {
                    $result = Lang::get('winter.pages::lang.page.new');
                }
                break;

            case 'menu':
                $result = $object->name;
                if (!strlen($result)) {
                    $result = Lang::get('winter.pages::lang.menu.new');
                }
                break;

            case 'content':
                $result = in_array($type, ['asset', 'content'])
                    ? $object->getFileName()
                    : $object->getBaseFileName();

                if (!$result) {
                    $result = Lang::get('cms::lang.'.$type.'.new');
                }
                break;

            default:
                $result = $object->getFileName();
                break;
        }

        return $result;
    }

    protected function pushObjectForm($type, $object, $alias = null)
    {
        $widget = $this->makeObjectFormWidget($type, $object, $alias);
        $widget->bindToController();

        $this->vars['canCommit'] = $this->canCommitObject($object);
        $this->vars['canReset'] = $this->canResetObject($object);
        $this->vars['objectPath'] = Request::input('path');
        $this->vars['lastModified'] = DateTime::makeCarbon($object->mtime);

        if ($type == 'page') {
            $this->vars['pageUrl'] = Url::to($object->getViewBag()->property('url'));
        }

        return [
            'tabTitle' => $this->getTabTitle($type, $object),
            'tab'      => $this->makePartial('form_page', [
                'form'         => $widget,
                'objectType'   => $type,
                'objectTheme'  => $this->theme->getDirName(),
                'objectMtime'  => $object->mtime,
                'objectParent' => Request::input('parentFileName')
            ])
        ];
    }

    protected function bindFormWidgetToController()
    {
        $alias = Request::input('formWidgetAlias');
        $type = $this->getObjectType();
        $objectPath = trim(Request::input('objectPath'));

        if (!$objectPath) {
            $object = ObjectHelper::createObject($this->theme, $type);
        } else {
            $object = ObjectHelper::loadObject($this->theme, $type, $objectPath);
        }

        // Set page layout super early because it cascades to other elements
        $layout = post('viewBag[layout]');
        if ($type === 'page' && !is_null($layout)) {
            $object->getViewBag()->setProperty('layout', $layout);
        }

        $widget = $this->makeObjectFormWidget($type, $object, $alias);
        $widget->bindToController();
    }

    /**
     * Replaces Windows style (/r/n) line endings with unix style (/n)
     * line endings.
     * @param string $markup The markup to convert to unix style endings
     * @return string
     */
    protected function convertLineEndings($markup)
    {
        $markup = str_replace("\r\n", "\n", $markup);
        $markup = str_replace("\r", "\n", $markup);

        return $markup;
    }

    /**
     * Returns a list of content files
     * @return \Winter\Storm\Database\Collection
     */
    protected function getContentTemplateList()
    {
        $templates = Content::listInTheme($this->theme, true);

        /**
         * @event pages.content.templateList
         * Provides opportunity to filter the items returned to the ContentList widget used by the Winter.Pages plugin in the backend.
         *
         * >**NOTE**: Recommended to just use cms.object.listInTheme instead
         *
         * Parameter provided is `$templates` (a collection of the Content CmsObjects being returned).
         * > Note: The `$templates` parameter provided is an object reference to a CmsObjectCollection, to make changes you must use object modifying methods.
         *
         * Example usage (only shows allowed content files):
         *
         *      \Event::listen('pages.content.templateList', function ($templates) {
         *           foreach ($templates as $index = $content) {
         *               if (!in_array($content->fileName, $allowedContent)) {
         *                   $templates->forget($index);
         *               }
         *           }
         *       });
         *
         * Or:
         *
         *     \Winter\Pages\Controller\Index::extend(function ($controller) {
         *           $controller->bindEvent('content.templateList', function ($templates) {
         *               foreach ($templates as $index = $content) {
         *                   if (!in_array($content->fileName, $allowedContent)) {
         *                       $templates->forget($index);
         *                   }
         *               }
         *           });
         *      });
         * }
         */
        if (
            ($event = $this->fireEvent('content.templateList', [$templates], true)) ||
            ($event = Event::fire('pages.content.templateList', [$this, $templates], true))
        ) {
            return $event;
        }

        return $templates;
    }
}
