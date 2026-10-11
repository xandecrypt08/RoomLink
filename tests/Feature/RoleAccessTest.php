<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function protectedUrls(): array
    {
        return [
            'admin dashboard' => ['/admin/dashboard'],
            'admin rooms' => ['/admin/rooms'],
            'admin schedules' => ['/admin/schedules'],
            'faculty dashboard' => ['/faculty/dashboard'],
            'student dashboard' => ['/student/dashboard'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function forbiddenRoleUrls(): array
    {
        return [
            'faculty on admin dashboard' => ['faculty', '/admin/dashboard'],
            'faculty on admin rooms' => ['faculty', '/admin/rooms'],
            'student on admin dashboard' => ['student', '/admin/dashboard'],
            'student on admin rooms' => ['student', '/admin/rooms'],
            'admin on faculty dashboard' => ['admin', '/faculty/dashboard'],
            'student on faculty dashboard' => ['student', '/faculty/dashboard'],
            'admin on student dashboard' => ['admin', '/student/dashboard'],
            'faculty on student dashboard' => ['faculty', '/student/dashboard'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function roleDashboards(): array
    {
        return [
            'admin' => ['admin', 'admin.dashboard'],
            'faculty' => ['faculty', 'faculty.dashboard'],
            'student' => ['student', 'student.dashboard'],
        ];
    }

    #[DataProvider('protectedUrls')]
    public function test_guest_is_redirected_to_login(string $url): void
    {
        $this->get($url)->assertRedirect(route('login'));
    }

    #[DataProvider('forbiddenRoleUrls')]
    public function test_user_cannot_open_another_roles_pages(string $role, string $url): void
    {
        $user = User::factory()->state(['role' => $role])->create();

        $this->actingAs($user)->get($url)->assertForbidden();
    }

    #[DataProvider('roleDashboards')]
    public function test_user_can_open_their_own_dashboard(string $role, string $dashboardRoute): void
    {
        $user = User::factory()->state(['role' => $role])->create();

        $this->actingAs($user)->get(route($dashboardRoute))->assertOk();
    }

    #[DataProvider('roleDashboards')]
    public function test_login_redirects_to_the_users_dashboard(string $role, string $dashboardRoute): void
    {
        $user = User::factory()->state(['role' => $role])->create();

        $response = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route($dashboardRoute));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'The email or password is incorrect.',
        ]);
        $this->assertGuest();
    }

    #[DataProvider('roleDashboards')]
    public function test_logged_in_user_visiting_login_page_is_sent_to_their_dashboard(string $role, string $dashboardRoute): void
    {
        $user = User::factory()->state(['role' => $role])->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect(route($dashboardRoute));
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
