<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_errors_use_the_toxbot_pages(): void
    {
        $this->get('/does-not-exist')->assertNotFound()
            ->assertSee(__('errors.404_title'))
            ->assertSee(__('errors.home'))
            ->assertSee('toxbot');

        foreach ([403, 419, 429, 500, 503] as $code) {
            $html = view('errors.'.$code)->render();
            $this->assertStringContainsString(__('errors.'.$code.'_title'), $html);
        }
    }
}
