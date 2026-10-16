<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QrScannerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_faculty_can_open_the_scanner(): void
    {
        $user = User::factory()->faculty()->create();
        Faculty::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('faculty.scanner'))
            ->assertOk()
            ->assertSee('capture="environment"', false)
            ->assertSee('js/vendor/jsQR-1.4.0.js')
            ->assertSee('js/qr-scanner.js');
    }

    public function test_scanner_opens_rooms_on_the_address_in_use_not_the_one_in_the_qr(): void
    {
        config(['app.url' => 'http://10.10.223.23:8000']);

        $user = User::factory()->faculty()->create();
        Faculty::factory()->for($user)->create();

        // A relative path, so the browser stays on whatever address it is using.
        $this->actingAs($user)
            ->get(route('faculty.scanner'))
            ->assertSee('data-scan-url="/faculty/scan/__CODE__"', false);
    }

    public function test_only_faculty_can_open_the_scanner(): void
    {
        $this->get(route('faculty.scanner'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('faculty.scanner'))
            ->assertForbidden();
    }

    public function test_dashboard_offers_the_scanner_during_a_class(): void
    {
        $this->travelTo('2026-10-12 08:05:00');

        $user = User::factory()->faculty()->create();
        $faculty = Faculty::factory()->for($user)->create();
        ClassSession::factory()->for($faculty)->create(['room_id' => Room::factory()->create()->id]);

        $this->actingAs($user)
            ->get(route('faculty.dashboard'))
            ->assertOk()
            ->assertSee('data-qr-scanner', false)
            ->assertSee('Scan Room QR');
    }
}
