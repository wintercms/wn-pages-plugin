<?php

namespace Winter\Pages\Classes;

use Cms\Classes\CmsException;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Winter\Storm\Parse\Syntax\Parser as SyntaxParser;
use Winter\Storm\Support\Str;

/**
 * Represents a static page controller.
 *
 * @package winter\pages
 * @author Alexey Bobkov, Samuel Georges
 */
class Controller
{
    use \Winter\Storm\Support\Traits\Singleton;

    protected $theme;

    /**
     * Initialize this singleton.
     */
    protected function init()
    {
        $this->theme = Theme::getActiveTheme();
        if (!$this->theme) {
            throw new CmsException(Lang::get('cms::lang.theme.active.not_found'));
        }
    }

    /**
     * Creates a CMS page from a static page and configures it.
     * @param string $url Specifies the static page URL.
     * @return \Cms\Classes\Page Returns the CMS page object or NULL of the requested page was not found.
     */
    public function initCmsPage($url)
    {
        $router = new Router($this->theme);
        $page = $router->findByUrl($url);

        if (!$page) {
            // Attempt to render a page preview if one exists
            if (!Str::startsWith($url, '/winter.pages/preview/')) {
                return null;
            }

            $alias = Str::after($url, '/winter.pages/preview/');
            $objectType = 'page';

            $data = Cache::get(ObjectHelper::getTypePreviewSessionCacheKey($objectType, $alias));

            if (empty($data)) {
                return null;
            }

            try {
                $page = ObjectHelper::fillObject(
                    $this->theme,
                    $objectType,
                    $data['objectPath'] ?? $data['fileName'] ?? '',
                    $data
                );
            } catch (\Throwable $e) {
                Log::error($e->getMessage(), $e->getTrace());
                return null;
            }
        }

        $viewBag = $page->viewBag;

        $cmsPage = CmsPage::inTheme($this->theme);
        $cmsPage->url = $url;
        $cmsPage->apiBag['staticPage'] = $page;

        /*
         * Transfer specific values from the content view bag to the page settings object.
         */
        $viewBagToSettings = ['title', 'layout', 'meta_title', 'meta_description', 'is_hidden'];

        foreach ($viewBagToSettings as $property) {
            $cmsPage->settings[$property] = array_get($viewBag, $property);
        }

        // Transer page ID to CMS page
        $cmsPage->settings['id'] = $page->getId();

        return $cmsPage;
    }

    public function injectPageTwig($page, $loader, $twig)
    {
        if (!isset($page->apiBag['staticPage'])) {
            return;
        }

        $staticPage = $page->apiBag['staticPage'];

        CmsException::mask($staticPage, 400);
        $loader->setObject($staticPage);
        $template = $twig->load($staticPage->getFilePath());
        $template->render([]);
        CmsException::unmask();
    }

    public function getPageContents($page)
    {
        if (!isset($page->apiBag['staticPage'])) {
            return;
        }

        return $page->apiBag['staticPage']->getProcessedMarkup();
    }

    public function getPlaceholderContents($page, $placeholderName, $placeholderContents)
    {
        if (!isset($page->apiBag['staticPage'])) {
            return;
        }

        return $page->apiBag['staticPage']->getProcessedPlaceholderMarkup($placeholderName, $placeholderContents);
    }

    public function initPageComponents($cmsController, $page)
    {
        if (!isset($page->apiBag['staticPage'])) {
            return;
        }

        $page->apiBag['staticPage']->initCmsComponents($cmsController);
    }

    public function parseSyntaxFields($content)
    {
        try {
            return SyntaxParser::parse($content, [
                'varPrefix' => 'extraData.',
                'tagPrefix' => 'page:',
            ])->toTwig();
        } catch (Exception $ex) {
            return $content;
        }
    }
}
