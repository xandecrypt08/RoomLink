<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\EnsureAdminUsesComputer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DeviceAwareLoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

    private const ANDROID_PHONE = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36';

    private const ANDROID_TABLET = 'Mozilla/5.0 (Linux; Android 14; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    private const IPAD = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15';

    private const WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    public function test_login_screen_is_for_faculty_and_students_on_a_phone(): void
    {
        $this->withHeader('User-Agent', self::IPHONE)
            ->get(route('login'))
            ->assertOk()
            ->assertSee('FACULTY &amp; STUDENTS', false)
            ->assertSee('Administrators: please sign in from a computer.');
    }

    public function test_login_screen_is_for_administrators_and_faculty_on_a_computer(): void
    {
        $this->withHeader('User-Agent', self::WINDOWS)
            ->get(route('login'))
            ->assertOk()
            ->assertSee('ADMINISTRATORS &amp; FACULTY', false)
            ->assertSee('Faculty and students can also sign in on their phones.');
    }

    public function test_administrators_cannot_sign_in_on_a_phone(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([self::IPHONE, self::ANDROID_PHONE] as $phone) {
            $this->withHeader('User-Agent', $phone)
                ->from(route('login'))
                ->post(route('login.submit'), ['email' => $admin->email, 'password' => 'password'])
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors(['email' => EnsureAdminUsesComputer::MESSAGE]);

            $this->assertGuest();
        }
    }

    public function test_administrators_can_sign_in_on_a_computer_or_tablet(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([self::WINDOWS, self::ANDROID_TABLET, self::IPAD] as $computer) {
            $this->withHeader('User-Agent', $computer)
                ->post(route('login.submit'), ['email' => $admin->email, 'password' => 'password'])
                ->assertRedirect(route('admin.dashboard'));

            $this->assertAuthenticatedAs($admin);

            $this->post(route('logout'));
        }
    }

    public function test_faculty_and_students_can_sign_in_on_any_device(): void
    {
        $users = [
            'faculty.dashboard' => User::factory()->faculty()->create(),
            'student.dashboard' => User::factory()->student()->create(),
        ];

        foreach ($users as $dashboard => $user) {
            foreach ([self::IPHONE, self::WINDOWS] as $device) {
                $this->withHeader('User-Agent', $device)
                    ->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])
                    ->assertRedirect(route($dashboard));

                $this->assertAuthenticatedAs($user);

                $this->post(route('logout'));
            }
        }
    }

    public function test_the_mobile_client_hint_decides_when_present(): void
    {
        $admin = User::factory()->admin()->create();

        // A phone browser reporting itself as mobile, even with a desktop user agent.
        $this->withHeaders(['User-Agent' => self::WINDOWS, 'Sec-CH-UA-Mobile' => '?1'])
            ->post(route('login.submit'), ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_administrator_session_used_on_a_phone_is_signed_out(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withHeader('User-Agent', self::ANDROID_PHONE)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => EnsureAdminUsesComputer::MESSAGE]);

        $this->assertGuest();
    }

    public function test_sign_in_is_locked_after_too_many_failed_attempts(): void
    {
        $user = User::factory()->faculty()->create();

        for ($attempt = 1; $attempt <= LoginController::MAX_ATTEMPTS; $attempt++) {
            $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'wrong-password'])
                ->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);
        }

        // Even the correct password is refused during the lockout.
        $response = $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Too many sign-in attempts.', session('errors')->first('email'));
        $this->assertGuest();

        // The lockout ends after a minute.
        $this->travel(LoginController::LOCKOUT_SECONDS + 1)->seconds();

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('faculty.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_successful_sign_in_resets_the_failed_attempt_count(): void
    {
        $user = User::factory()->faculty()->create();

        for ($attempt = 1; $attempt < LoginController::MAX_ATTEMPTS; $attempt++) {
            $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password']);
        $this->post(route('logout'));

        // A fresh count: one more mistake is not a lockout.
        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);
    }
}
