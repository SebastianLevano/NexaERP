<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithViews;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
