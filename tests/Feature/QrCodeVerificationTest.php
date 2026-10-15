<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\RetiredQrCode;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class QrCodeVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $facultyUser;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Monday 2026-10-12, 08:05, inside the default 08:00-10:00 Monday class.
        $this->travelTo(Carbon::parse('2026-10-12 08:05:00'));

        $this->admin = User::factory()->admin()->create();
        $this->facultyUser = User::factory()->faculty()->create();
        $this->room = Room::factory()->create(['room_name' => 'Room 101']);

        ClassSession::factory()
            ->for(Faculty::factory()->for($this->facultyUser))
            ->create(['room_id' => $this->room->id]);
    }

    public function test_admin_can_regenerate_a_room_qr_code(): void
    {
        $oldCode = $this->room->qr_code;

        $response = $this->actingAs($this->admin)->post(route('rooms.qr.regenerate', $this->room));

        $response->assertRedirect(route('rooms.qr', $this->room));
        $response->assertSessionHas('success');

        $newCode = $this->room->fresh()->qr_code;
        $this->assertNotSame($oldCode, $newCode);
        $this->assertMatchesRegularExpression('/^ROOM-[A-Z0-9]{10}$/', $newCode);
        $this->assertDatabaseHas('retired_qr_codes', [
            'room_id' => $this->room->id,
            'qr_code' => $oldCode,
            'retired_at' => '2026-10-12 08:05:00',
        ]);
    }

    public function test_every_replaced_code_stays_retired(): void
    {
        $firstCode = $this->room->qr_code;
        $secondCode = $this->room->regenerateQrCode();
        $thirdCode = $this->room->regenerateQrCode();

        $this->assertSame($thirdCode, $this->room->qr_code);
        $this->assertCount(3, array_unique([$firstCode, $secondCode, $thirdCode]));
        $this->assertSame(
            [$firstCode, $secondCode],
            $this->room->retiredQrCodes()->orderBy('id')->pluck('qr_code')->all()
        );
    }

    public function test_only_admins_can_regenerate_qr_codes(): void
    {
        $oldCode = $this->room->qr_code;

        $this->actingAs($this->facultyUser)
            ->post(route('rooms.qr.regenerate', $this->room))
            ->assertForbidden();

        $this->assertSame($oldCode, $this->room->fresh()->qr_code);
    }

    public function test_qr_page_shows_the_regenerate_button_and_replaced_codes(): void
    {
        $oldCode = $this->room->qr_code;
        $this->room->regenerateQrCode();

        $response = $this->actingAs($this->admin)->get(route('rooms.qr', $this->room));

        $response->assertOk();
        $response->assertSee('Regenerate QR Code');
        $response->assertSee($oldCode);
        $response->assertViewHas('qrData', route('faculty.sessions.scan', $this->room->qr_code));
    }

    public function test_scanning_a_replaced_qr_code_explains_it_no_longer_works(): void
    {
        $oldCode = $this->room->qr_code;
        $this->room->regenerateQrCode();

        $response = $this->actingAs($this->facultyUser)->get(route('faculty.sessions.scan', $oldCode));

        $response->assertOk();
        $response->assertViewHas('room', null);
        $response->assertViewHas('blocker', fn (string $blocker) => str_contains($blocker, 'This QR code for Room 101 was replaced on Oct 12, 2026'));
        $response->assertDontSee('Start Session');
    }

    public function test_class_cannot_be_started_with_a_replaced_qr_code(): void
    {
        $oldCode = $this->room->qr_code;
        $this->room->regenerateQrCode();

        $response = $this->actingAs($this->facultyUser)->post(route('faculty.sessions.store'), ['code' => $oldCode]);

        $response->assertRedirect(route('faculty.sessions.scan', $oldCode));
        $this->assertDatabaseCount('room_sessions', 0);
        $this->assertSame('available', $this->room->fresh()->status);
    }

    public function test_class_can_be_started_with_the_new_qr_code(): void
    {
        $newCode = $this->room->regenerateQrCode();

        $this->actingAs($this->facultyUser)
            ->get(route('faculty.sessions.scan', $newCode))
            ->assertViewHas('blocker', null);

        $this->actingAs($this->facultyUser)
            ->post(route('faculty.sessions.store'), ['code' => $newCode])
            ->assertRedirect(route('faculty.dashboard'));

        $this->assertSame('occupied', $this->room->fresh()->status);
    }

    public function test_unknown_qr_code_still_shows_the_generic_message(): void
    {
        $response = $this->actingAs($this->facultyUser)->get(route('faculty.sessions.scan', 'ROOM-NOTAREALCODE'));

        $response->assertViewHas('blocker', 'This QR code does not belong to any RoomLink classroom. Report the damaged QR code to the administrator.');
    }

    public function test_temporary_request_page_explains_a_replaced_code(): void
    {
        $oldCode = $this->room->qr_code;
        $this->room->regenerateQrCode();

        $response = $this->actingAs($this->facultyUser)
            ->get(route('faculty.temporary-requests.create', ['code' => $oldCode]));

        $response->assertOk();
        $response->assertViewHas('retiredQrCode', fn (?RetiredQrCode $retired) => $retired?->qr_code === $oldCode);
        $response->assertSee('was replaced on Oct 12, 2026');
    }

    public function test_new_qr_codes_never_reuse_a_retired_code(): void
    {
        RetiredQrCode::factory()->create(['qr_code' => 'ROOM-AAAAAAAAAA']);
        // Random draws: a retired code, the room's current code, then a free one.
        Str::createRandomStringsUsingSequence(['AAAAAAAAAA', Str::after($this->room->qr_code, 'ROOM-'), 'BBBBBBBBBB']);

        try {
            $this->assertSame('ROOM-BBBBBBBBBB', Room::newQrCode());
        } finally {
            Str::createRandomStringsNormally();
        }
    }
}
