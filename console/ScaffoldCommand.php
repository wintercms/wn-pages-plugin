<?php

namespace Winter\Pages\Console;

use Backend;
use Cms\Classes\Layout;
use Cms\Classes\Partial;
use Cms\Classes\Theme;
use File;
use Illuminate\Console\Command;
use Winter\Pages\Classes\Content;
use Winter\Pages\Classes\Menu;
use Winter\Pages\Classes\Page as StaticPage;
use Winter\Pages\Plugin as PagesPlugin;

/**
 * Scaffolds Winter.Pages demo content for local development and testing.
 *
 * Winter.Pages stores its content (static pages, menus, content blocks and
 * snippets) as *files in the active theme* via the CMS theme object model — not
 * as database records. This command therefore creates theme content objects
 * (through the plugin's own Page/Menu/Content classes + a snippet partial) in the
 * edit theme so every backend surface can be exercised: the Pages side-panel tree
 * (incl. a nested hierarchy and a very long title), the page/menu/content editors,
 * and the snippet list.
 *
 * Mirrors the env-guarded, idempotent `scaffold:*` pattern used elsewhere. Every
 * created object is marked with a `scaffold-` filename prefix so `--fresh` can
 * scope its cleanup to scaffold content only.
 */
class ScaffoldCommand extends Command
{
    protected $signature = 'scaffold:winter.pages
        {--fresh : Delete any existing scaffold content before recreating it}';

    protected $description = 'Scaffold Winter.Pages demo content (theme static pages, menus, content blocks + a snippet) for local development/testing.';

    /**
     * Filename prefix marking every scaffold-created object, so `--fresh` deletion
     * (and the idempotency check) can be scoped to scaffold content only.
     */
    const PREFIX = 'scaffold-';

    /**
     * The theme layout the scaffold pages use. It is created by this command (also
     * `scaffold-` prefixed) and wires up the `staticPage` component so the pages
     * resolve a usable layout in the editor's layout dropdown.
     */
    const LAYOUT = 'scaffold-static-layout';

    protected Theme $theme;

    public function handle(): int
    {
        // Never inject demo content into a production install.
        if ($this->getLaravel()->environment('production')) {
            $this->error('scaffold:winter.pages cannot run in the production environment.');

            return self::FAILURE;
        }

        if (!($theme = Theme::getEditTheme())) {
            $this->error('No active/edit theme found — cannot scaffold theme content.');

            return self::FAILURE;
        }
        $this->theme = $theme;
        $this->line('Scaffolding into theme: ' . $this->theme->getDirName());

        if ($this->option('fresh')) {
            $this->deleteExisting();
        }

        if ($this->scaffoldPageExists()) {
            $this->warn('Winter.Pages scaffold content already exists. Use --fresh to recreate it.');

            return self::SUCCESS;
        }

        $this->createLayout();

        $pageCount = $this->createPages();
        $this->info("Created {$pageCount} static page(s).");

        $this->createMenu();
        $this->info('Created 1 menu (with nested items).');

        $contentCount = $this->createContentBlocks();
        $this->info("Created {$contentCount} content block(s).");

        $this->createSnippet();
        $this->info('Created 1 snippet (partial).');

        // Clear cached menu/router/snippet state so the new content is picked up.
        PagesPlugin::clearCache();

        $this->printCaptureTargets();

        return self::SUCCESS;
    }

    /**
     * Determine whether scaffold content already exists (used for idempotency).
     */
    protected function scaffoldPageExists(): bool
    {
        foreach (StaticPage::listInTheme($this->theme, true) as $page) {
            if (str_starts_with($page->getBaseFileName(), self::PREFIX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove all previously scaffolded content. Deletion is by `scaffold-` filename
     * prefix so it is robust even if the meta index / nesting is out of sync.
     */
    protected function deleteExisting(): void
    {
        $removed = 0;

        // Pages — delete deepest first so parent recursion doesn't fight the glob.
        $pages = [];
        foreach (StaticPage::listInTheme($this->theme, true) as $page) {
            if (str_starts_with($page->getBaseFileName(), self::PREFIX)) {
                $pages[$page->getBaseFileName()] = $page;
            }
        }
        // Deepest paths (most segments) first.
        uksort($pages, fn ($a, $b) => substr_count($b, '/') <=> substr_count($a, '/'));
        foreach ($pages as $page) {
            try {
                $page->delete();
                $removed++;
            } catch (\Throwable $e) {
                // Already gone via a parent's recursive delete; ignore.
            }
        }

        // Menus.
        foreach (Menu::listInTheme($this->theme, true) as $menu) {
            if (str_starts_with($menu->getBaseFileName(), self::PREFIX)) {
                $menu->delete();
                $removed++;
            }
        }

        // Content blocks.
        foreach (Content::listInTheme($this->theme, true) as $content) {
            if (str_starts_with($content->getBaseFileName(), self::PREFIX)) {
                $content->delete();
                $removed++;
            }
        }

        // Snippet partial + scaffold layout.
        foreach (Partial::listInTheme($this->theme, true) as $partial) {
            if (str_starts_with($partial->getBaseFileName(), self::PREFIX)) {
                $partial->delete();
                $removed++;
            }
        }
        foreach (Layout::listInTheme($this->theme, true) as $layout) {
            if (str_starts_with($layout->getBaseFileName(), self::PREFIX)) {
                $layout->delete();
                $removed++;
            }
        }

        // Belt-and-suspenders: sweep any orphaned scaffold files left on disk
        // (e.g. a child page orphaned from the meta tree).
        $dir = rtrim($this->theme->getPath(), '/');
        foreach ([
            $dir . '/content/static-pages',
            $dir . '/meta/menus',
            $dir . '/content',
            $dir . '/partials',
            $dir . '/layouts',
        ] as $sub) {
            if (!File::isDirectory($sub)) {
                continue;
            }
            foreach (File::glob($sub . '/' . self::PREFIX . '*') as $file) {
                if (File::isFile($file)) {
                    File::delete($file);
                    $removed++;
                }
            }
        }

        if ($removed > 0) {
            $this->info("Removed {$removed} existing scaffold object(s)/file(s).");
        }
    }

    /**
     * Create a theme layout wired with the `staticPage` component so scaffold pages
     * resolve a usable layout in the editor (the stock demo layout has none).
     */
    protected function createLayout(): void
    {
        if (Layout::load($this->theme, self::LAYOUT . '.htm')) {
            return;
        }

        $layout = Layout::inTheme($this->theme);
        $layout->fileName = self::LAYOUT . '.htm';
        $layout->markup = <<<'TWIG'
description = "Scaffold Static Layout"

[staticPage]
useContent = 1
default = 0
==
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ this.page.title }}</title>
</head>
<body>
    <h1>{{ this.page.title }}</h1>
    {% page %}
    {% placeholder scaffoldSidebar title="Sidebar" %}
</body>
</html>
TWIG;
        $layout->save();
    }

    /**
     * Create the static pages: a nested three-level hierarchy, a page with a
     * deliberately long title, and a couple of flat pages — enough to populate the
     * Pages side-panel tree and every page-editor field.
     */
    protected function createPages(): int
    {
        // Root: About (has children).
        $about = $this->makePage('About Us', '/scaffold-about', [
            'markup' => "<h2>About us</h2>\n<p>Root scaffold page with child pages nested underneath it in the tree.</p>",
        ]);

        // Child of About.
        $team = $this->makePage('Our Team', '/scaffold-about/team', [
            'parent' => $about,
            'markup' => "<h2>Our team</h2>\n<p>A nested (level 2) static page.</p>",
        ]);

        // Grandchild of About > Team (level 3).
        $this->makePage('Leadership', '/scaffold-about/team/leadership', [
            'parent' => $team,
            'markup' => "<h2>Leadership</h2>\n<p>A deeply nested (level 3) static page to exercise the tree indentation.</p>",
        ]);

        // Sibling child of About — hidden from navigation.
        $this->makePage('Careers', '/scaffold-about/careers', [
            'parent' => $about,
            'navigation_hidden' => 1,
            'markup' => "<h2>Careers</h2>\n<p>This page is hidden from navigation (navigation_hidden = 1).</p>",
        ]);

        // Flat page with a placeholder + meta fields populated.
        $this->makePage('Contact', '/scaffold-contact', [
            'markup' => "<h2>Contact</h2>\n<p>A flat scaffold page with meta fields and a placeholder value.</p>",
            'meta_title' => 'Contact us — scaffold',
            'meta_description' => 'Scaffold contact page used to exercise the meta tab in the page editor.',
            'placeholders' => ['scaffoldSidebar' => '<p>Sidebar placeholder content.</p>'],
        ]);

        // Deliberately very long title to test truncation/wrapping in the tree,
        // breadcrumb and editor tab labels.
        $this->makePage(
            'A deliberately very long static page title that exists purely to test how the Pages '
            . 'side-panel tree, the editor tab label and the breadcrumb handle text that simply refuses '
            . 'to end and keeps going well past any reasonable length',
            '/scaffold-long-title',
            ['markup' => "<h2>Long title</h2>\n<p>The point of this page is the title length.</p>"]
        );

        return 6;
    }

    /**
     * Create a single static page object in the edit theme.
     */
    protected function makePage(string $title, string $url, array $opts = []): StaticPage
    {
        $viewBag = [
            'title'             => $title,
            'url'               => $url,
            'layout'            => self::LAYOUT,
            'is_hidden'         => $opts['is_hidden'] ?? 0,
            'navigation_hidden' => $opts['navigation_hidden'] ?? 0,
        ];
        if (!empty($opts['meta_title'])) {
            $viewBag['meta_title'] = $opts['meta_title'];
        }
        if (!empty($opts['meta_description'])) {
            $viewBag['meta_description'] = $opts['meta_description'];
        }

        $page = StaticPage::inTheme($this->theme);

        if (!empty($opts['parent'])) {
            /** @var StaticPage $parent */
            $parent = $opts['parent'];
            // PageList::appendPage matches parentFileName against the meta tree
            // keys, which are *base* file names (no extension).
            $page->parentFileName = $parent->getBaseFileName();
        }

        $fill = [
            'settings' => ['viewBag' => $viewBag],
            'markup'   => $opts['markup'] ?? "<p>Scaffold page.</p>",
        ];
        if (!empty($opts['placeholders'])) {
            $fill['placeholders'] = $opts['placeholders'];
        }

        $page->fill($fill);
        $page->save();

        return $page;
    }

    /**
     * Create a menu with a nested item hierarchy: URL items, a header, and
     * static-page references pointing at the scaffold pages.
     */
    protected function createMenu(): void
    {
        $menu = Menu::inTheme($this->theme);
        $menu->fill([
            'name'     => 'Scaffold Main Menu',
            'code'     => self::PREFIX . 'main-menu',
            'itemData' => [
                [
                    'title'     => 'Home',
                    'type'      => 'url',
                    'url'       => '/',
                    'code'      => self::PREFIX . 'home',
                    'reference' => null,
                    'replace'   => false,
                    'viewBag'   => ['isHidden' => '0', 'cssClass' => '', 'isExternal' => '0'],
                    'items'     => [],
                ],
                [
                    'title'     => 'About',
                    'type'      => 'static-page',
                    'reference' => 'scaffold-about',
                    'nesting'   => true,
                    'code'      => self::PREFIX . 'about',
                    'replace'   => false,
                    'viewBag'   => ['isHidden' => '0', 'cssClass' => '', 'isExternal' => '0'],
                    'items'     => [
                        [
                            'title'     => 'Our Team',
                            'type'      => 'static-page',
                            'reference' => 'scaffold-about-team',
                            'code'      => self::PREFIX . 'team',
                            'replace'   => false,
                            'viewBag'   => ['isHidden' => '0', 'cssClass' => '', 'isExternal' => '0'],
                            'items'     => [
                                [
                                    'title'     => 'Leadership',
                                    'type'      => 'static-page',
                                    'reference' => 'scaffold-about-team-leadership',
                                    'code'      => self::PREFIX . 'leadership',
                                    'replace'   => false,
                                    'viewBag'   => ['isHidden' => '0', 'cssClass' => '', 'isExternal' => '0'],
                                    'items'     => [],
                                ],
                            ],
                        ],
                        [
                            'title'     => 'Contact',
                            'type'      => 'static-page',
                            'reference' => 'scaffold-contact',
                            'code'      => self::PREFIX . 'contact',
                            'replace'   => false,
                            'viewBag'   => ['isHidden' => '0', 'cssClass' => '', 'isExternal' => '0'],
                            'items'     => [],
                        ],
                    ],
                ],
                [
                    'title'     => 'Resources',
                    'type'      => 'header',
                    'code'      => self::PREFIX . 'resources-header',
                    'replace'   => false,
                    'viewBag'   => ['isHidden' => '0', 'cssClass' => '', 'isExternal' => '0'],
                    'items'     => [
                        [
                            'title'     => 'Winter CMS',
                            'type'      => 'url',
                            'url'       => 'https://wintercms.com',
                            'code'      => self::PREFIX . 'external',
                            'replace'   => false,
                            'viewBag'   => ['isHidden' => '0', 'cssClass' => '', 'isExternal' => '1'],
                            'items'     => [],
                        ],
                    ],
                ],
            ],
        ]);
        $menu->save();
    }

    /**
     * Create a couple of reusable content blocks (an HTML block and a plain-text
     * block) that appear under the Content list in the side panel.
     */
    protected function createContentBlocks(): int
    {
        $html = Content::inTheme($this->theme);
        $html->fileName = self::PREFIX . 'promo.htm';
        $html->markup = "<div class=\"promo\">\n    <h3>Scaffold promo block</h3>\n    <p>A reusable HTML content block edited via the rich editor.</p>\n</div>";
        $html->save();

        $text = Content::inTheme($this->theme);
        $text->fileName = self::PREFIX . 'disclaimer.txt';
        $text->markup = "Scaffold disclaimer — a plain-text content block edited via the code editor.";
        $text->save();

        return 2;
    }

    /**
     * Create a snippet. Snippets in Winter.Pages are CMS partials that declare
     * `snippetCode` + `snippetName` in their viewBag; they then appear in the
     * Snippets list in the Pages side panel.
     */
    protected function createSnippet(): void
    {
        $partial = Partial::inTheme($this->theme);
        $partial->fileName = self::PREFIX . 'callout.htm';
        $partial->markup = <<<'TWIG'
[viewBag]
snippetCode = "scaffoldCallout"
snippetName = "Scaffold Callout"
snippetProperties[title][title] = "Title"
snippetProperties[title][type] = "string"
snippetProperties[title][default] = "Heads up"
==
<div class="callout">
    <strong>{{ title }}</strong>
    <p>A scaffold snippet rendered from a theme partial.</p>
</div>
TWIG;
        $partial->save();
    }

    /**
     * Print the backend capture targets for the dark-mode audit.
     */
    protected function printCaptureTargets(): void
    {
        $base = Backend::url('winter/pages');

        $this->newLine();
        $this->line('Backend capture targets (open in dark mode):');
        $this->line('  Pages index (side-panel tree): ' . $base);
        $this->line('  Side panels are tabbed within the index: Pages / Menus / Content / Snippets.');
        $this->line('  Open "About Us" (and its nested children) to capture the page editor.');
        $this->line('  Open "Scaffold Main Menu" to capture the menu editor (nested items).');
        $this->line('  Open the "Promo" / "Disclaimer" content blocks to capture the content editor.');
    }
}
