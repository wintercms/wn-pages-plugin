<?php

namespace Winter\User\Updates;

use Cms\Classes\Partial;
use Cms\Classes\Theme;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\File;

class SnippetsRenameViewbagProperties extends Migration
{
    public function up()
    {
        $themes = Theme::all();
        foreach ($themes as $theme) {
            $partials = Partial::inTheme($theme)->all();
            foreach ($partials as $partial) {
                try {
                    $path = $partial->getFilePath();
                    $contents = File::get($path);
                    if (strpos($contents, 'staticPageSnippetCode') === false) {
                        continue;
                    }
                    $contents = str_replace('staticPageSnippetName', 'snippetName', $contents);
                    $contents = str_replace('staticPageSnippetCode', 'snippetCode', $contents);
                    $contents = str_replace('staticPageSnippetProperties', 'snippetProperties', $contents);
                    File::put($path, $contents);
                } catch (\Exception $ex) {
                    continue;
                }
            }
        }
    }

    public function down()
    {
    }
}
