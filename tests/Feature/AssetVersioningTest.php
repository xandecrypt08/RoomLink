<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AssetVersioningTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_stylesheets_carry_a_version_so_browsers_load_changes(): void
    {
        $version = filemtime(public_path('css/ui.css'));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('subjects.index'))
            ->assertOk()
            ->assertSee('css/ui.css?v='.$version, false);

        $this->post(route('logout'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('css/auth.css?v='.filemtime(public_path('css/auth.css')), false);
    }
}
