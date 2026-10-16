<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PaginationLinksTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_use_roomlink_page_links_instead_of_the_tailwind_default(): void
    {
        Room::factory()->count(25)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('rooms.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('class="pager"', false)
            ->assertSeeInOrder(['Showing', '11', '20', 'of', '25'])
            ->assertSee(route('rooms.index', ['page' => 1]), false)
            ->assertSee(route('rooms.index', ['page' => 3]), false)
            ->assertDontSee('<svg', false);
    }

    public function test_no_page_links_when_everything_fits_on_one_page(): void
    {
        Room::factory()->count(3)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('rooms.index'))
            ->assertOk()
            ->assertDontSee('class="pager"', false);
    }
}
