<?php

namespace Flat3\Lodata\Tests\Command;

use Flat3\Lodata\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class CommandTest extends TestCase
{
    function test_function()
    {
        Artisan::call('lodata:function Example');
        $this->assertMatchesFileSnapshot(app_path('Lodata/Example.php'));
    }

    function test_action()
    {
        Artisan::call('lodata:action Example');
        $this->assertMatchesFileSnapshot(app_path('Lodata/Example.php'));
    }
}