<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array<string, string>
     */
    private function accountData(array $overrides = []): array
    {
        return [
            'first_name' => 'Maria',
            'middle_name' => '',
            'last_name' => 'Santos',
            'employee_student_id' => '2024-1234',
            'email' => 'maria.santos@lnu.edu.ph',
            'role' => 'student',
            'department' => 'BSIT',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            ...$overrides,
        ];
    }

    public function test_admin_can_list_accounts(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($this->admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee($student->email);
        $response->assertSee($this->admin->email);
    }

    public function test_accounts_can_be_searched_and_filtered_by_role(): void
    {
        $faculty = User::factory()->faculty()->create(['last_name' => 'Reyes']);
        $student = User::factory()->student()->create(['last_name' => 'Reyes']);
        $other = User::factory()->faculty()->create(['last_name' => 'Cruz']);

        $response = $this->actingAs($this->admin)->get(route('users.index', ['search' => 'Reyes', 'role' => 'faculty']));

        $response->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$faculty->id]);
        $response->assertDontSee($student->email);
        $response->assertDontSee($other->email);
    }

    public function test_non_admins_cannot_manage_accounts(): void
    {
        $faculty = User::factory()->faculty()->create();

        $this->actingAs($faculty)->get(route('users.index'))->assertForbidden();
        $this->actingAs($faculty)->post(route('users.store'), $this->accountData())->assertForbidden();
        $this->actingAs($faculty)->patch(route('users.active', $this->admin))->assertForbidden();
    }

    public function test_admin_can_create_an_account_that_can_log_in(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), $this->accountData());

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $user = User::where('email', 'maria.santos@lnu.edu.ph')->firstOrFail();
        $this->assertSame('student', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('secret-pass', $user->password));

        auth()->logout();
        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_faculty_account_can_be_linked_to_a_faculty_record(): void
    {
        $faculty = Faculty::factory()->create();

        $this->actingAs($this->admin)->post(route('users.store'), $this->accountData([
            'role' => 'faculty',
            'faculty_id' => $faculty->id,
        ]));

        $user = User::where('email', 'maria.santos@lnu.edu.ph')->firstOrFail();
        $this->assertTrue($faculty->fresh()->user->is($user));
    }

    public function test_faculty_record_already_linked_to_another_account_cannot_be_linked(): void
    {
        $faculty = Faculty::factory()->for(User::factory()->faculty())->create();

        $response = $this->actingAs($this->admin)->post(route('users.store'), $this->accountData([
            'role' => 'faculty',
            'faculty_id' => $faculty->id,
        ]));

        $response->assertSessionHasErrors('faculty_id');
        $this->assertDatabaseMissing('users', ['email' => 'maria.santos@lnu.edu.ph']);
    }

    public function test_faculty_record_is_ignored_for_non_faculty_accounts(): void
    {
        $faculty = Faculty::factory()->create();

        $this->actingAs($this->admin)->post(route('users.store'), $this->accountData([
            'role' => 'student',
            'faculty_id' => $faculty->id,
        ]))->assertSessionHasNoErrors();

        $this->assertNull($faculty->fresh()->user_id);
    }

    public function test_email_and_university_id_must_be_unique(): void
    {
        $existing = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('users.store'), $this->accountData([
            'email' => $existing->email,
            'employee_student_id' => $existing->employee_student_id,
        ]));

        $response->assertSessionHasErrors(['email', 'employee_student_id']);
    }

    public function test_password_must_be_confirmed_and_at_least_8_characters(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->accountData(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');

        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->accountData(['password_confirmation' => 'different-pass']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'maria.santos@lnu.edu.ph']);
    }

    public function test_admin_can_edit_an_account_without_changing_its_password(): void
    {
        $user = User::factory()->student()->create();
        $passwordHash = $user->password;

        $response = $this->actingAs($this->admin)->put(route('users.update', $user), $this->accountData([
            'email' => $user->email,
            'employee_student_id' => $user->employee_student_id,
            'department' => 'BSEd',
            'password' => null,
            'password_confirmation' => null,
        ]));

        $response->assertRedirect(route('users.index'));
        $user->refresh();
        $this->assertSame('BSEd', $user->department);
        $this->assertSame('Maria', $user->first_name);
        $this->assertSame($passwordHash, $user->password);
    }

    public function test_changing_a_faculty_account_to_another_role_unlinks_its_faculty_record(): void
    {
        $user = User::factory()->faculty()->create();
        $faculty = Faculty::factory()->for($user)->create();

        $this->actingAs($this->admin)->put(route('users.update', $user), $this->accountData([
            'email' => $user->email,
            'employee_student_id' => $user->employee_student_id,
            'role' => 'student',
            'faculty_id' => $faculty->id,
        ]))->assertSessionHasNoErrors();

        $this->assertNull($faculty->fresh()->user_id);
    }

    public function test_faculty_account_can_switch_to_a_different_faculty_record(): void
    {
        $user = User::factory()->faculty()->create();
        $oldRecord = Faculty::factory()->for($user)->create();
        $newRecord = Faculty::factory()->create();

        $this->actingAs($this->admin)->put(route('users.update', $user), $this->accountData([
            'email' => $user->email,
            'employee_student_id' => $user->employee_student_id,
            'role' => 'faculty',
            'faculty_id' => $newRecord->id,
        ]))->assertSessionHasNoErrors();

        $this->assertNull($oldRecord->fresh()->user_id);
        $this->assertTrue($newRecord->fresh()->user->is($user));
    }

    public function test_admin_cannot_remove_their_own_admin_role(): void
    {
        $response = $this->actingAs($this->admin)->put(route('users.update', $this->admin), $this->accountData([
            'email' => $this->admin->email,
            'employee_student_id' => $this->admin->employee_student_id,
            'role' => 'student',
        ]));

        $response->assertSessionHasErrors('role');
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_admin_can_reset_a_password(): void
    {
        $user = User::factory()->student()->create();

        $response = $this->actingAs($this->admin)->patch(route('users.password', $user), [
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ]);

        $response->assertRedirect(route('users.edit', $user));
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function test_deactivated_account_cannot_log_in(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($this->admin)->patch(route('users.active', $user))->assertRedirect(route('users.index'));
        $this->assertFalse($user->fresh()->is_active);

        auth()->logout();
        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Your account has been deactivated. Please contact the administrator.']);
        $this->assertGuest();
    }

    public function test_logged_in_user_is_signed_out_once_deactivated(): void
    {
        $user = User::factory()->student()->deactivated()->create();

        $response = $this->actingAs($user)->get(route('student.dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_deactivated_account_can_be_reactivated(): void
    {
        $user = User::factory()->student()->deactivated()->create();

        $this->actingAs($this->admin)->patch(route('users.active', $user));

        $this->assertTrue($user->fresh()->is_active);
        $this->post(route('logout'));
        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('users.active', $this->admin));

        $response->assertSessionHas('error', 'You cannot deactivate your own account.');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_account_pages_render(): void
    {
        $user = User::factory()->faculty()->create();
        Faculty::factory()->for($user)->create();
        Faculty::factory()->create();

        $this->actingAs($this->admin)->get(route('users.create'))->assertOk()->assertSee('Linked Faculty Record');
        $this->actingAs($this->admin)->get(route('users.edit', $user))->assertOk()->assertSee('Reset Password');
    }
}
