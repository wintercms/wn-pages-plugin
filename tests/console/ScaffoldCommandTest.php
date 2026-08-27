<?php

namespace Winter\Pages\Tests\Console;

use Artisan;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use PluginTestCase;
use Winter\Pages\Console\ScaffoldCommand;

/**
 * Guards the safety behaviour of the demo-content scaffolder.
 *
 * Winter.Pages stores its content as files in the active theme, not as database
 * records — this command writes static pages, menus, content blocks and a snippet
 * into the edit theme. That full theme-file seeding is verified against a real
 * install rather than here, so the isolated test does not mutate the on-disk demo
 * theme; it asserts the command is wired up and refuses to run in production.
 */
class ScaffoldCommandTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Plugin console commands are registered via ConsoleApplication::starting, which has
        // already fired by the time the test harness boots the plugin — so the command isn't
        // resolvable through Artisan here. Register it directly with the kernel for the test.
        $this->app->make(ConsoleKernel::class)->registerCommand(new ScaffoldCommand());
    }

    public function testCommandIsRegistered()
    {
        $this->assertArrayHasKey('scaffold:winter.pages', Artisan::all());
    }

    public function testRefusesToRunInProduction()
    {
        $this->app['env'] = 'production';

        $exitCode = Artisan::call('scaffold:winter.pages');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('production', Artisan::output());

        $this->app['env'] = 'testing';
    }
}
