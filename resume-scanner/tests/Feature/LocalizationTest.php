<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_guest_pages_render_swahili_strings_when_locale_is_sw(): void
    {
        $this->withSession(['locale' => 'sw'])
            ->get('/login')
            ->assertSee('Ingia');

        $this->withSession(['locale' => 'sw'])
            ->get('/register')
            ->assertSee('Jisajili');

        $this->withSession(['locale' => 'sw'])
            ->get('/')
            ->assertSee('Karibu');
    }
}
