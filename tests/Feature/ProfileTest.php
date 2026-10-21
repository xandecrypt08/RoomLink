<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function roles(): array
    {
        return ['admin' => ['admin'], 'faculty' => ['faculty'], 'student' => ['student']];
    }

    #[DataProvider('roles')]
    public function test_every_role_can_open_their_profile_from_the_top_bar(string $role): void
    {
        $user = User::factory()->{$role}()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee('href="'.route('profile.edit').'" class="profile-box"', false)
            ->assertSee('value="'.$user->email.'"', false);
    }

    public function test_guests_cannot_see_profiles(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_a_student_can_change_all_their_details(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->patch(route('profile.update'), [
                'first_name' => 'Maria',
                'middle_name' => 'Santos',
                'last_name' => 'Reyes',
                'employee_student_id' => '2401361',
                'email' => 'maria@lnu.edu.ph',
                'contact_number' => '0917 123 4567',
                'department' => 'BSIT',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', 'Your profile has been updated.');

        $student->refresh();

        $this->assertSame(['Maria', 'Santos', 'Reyes', '2401361', 'maria@lnu.edu.ph', '0917 123 4567', 'BSIT'], [
            $student->first_name, $student->middle_name, $student->last_name, $student->employee_student_id,
            $student->email, $student->contact_number, $student->department,
        ]);
    }

    public function test_an_administrator_can_change_their_name_and_id(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('profile.update'), [
                'first_name' => 'Alexander',
                'last_name' => 'Diocton',
                'employee_student_id' => 'ADM-001',
                'email' => 'admin@roomlink.edu.ph',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertSame(['Alexander', 'Diocton', 'ADM-001'], [$admin->fresh()->first_name, $admin->fresh()->last_name, $admin->fresh()->employee_student_id]);
    }

    public function test_faculty_can_change_their_contact_details_but_not_their_name_or_employee_number(): void
    {
        $user = User::factory()->faculty()->create(['first_name' => 'Janine', 'last_name' => 'Memoracion', 'employee_student_id' => '250702']);
        $faculty = Faculty::factory()->for($user)->create(['email' => $user->email]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => 'Someone',
                'last_name' => 'Else',
                'employee_student_id' => '999999',
                'department' => 'Changed',
                'role' => 'admin',
                'is_active' => '0',
                'email' => 'janine@lnu.edu.ph',
                'contact_number' => '+63 917 123 4567',
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame(['Janine', 'Memoracion', '250702', 'faculty', true], [$user->first_name, $user->last_name, $user->employee_student_id, $user->role, $user->is_active]);
        $this->assertNull($user->department);
        $this->assertSame(['janine@lnu.edu.ph', '+63 917 123 4567'], [$user->email, $user->contact_number]);
        $this->assertSame('janine@lnu.edu.ph', $faculty->fresh()->email, 'The faculty record keeps the same email.');
    }

    public function test_the_faculty_profile_shows_the_name_and_employee_number_as_locked(): void
    {
        $user = User::factory()->faculty()->create(['employee_student_id' => '250702']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee('value="250702" readonly', false)
            ->assertDontSee('name="first_name"', false)
            ->assertDontSee('name="employee_student_id"', false)
            ->assertSee('name="contact_number"', false)
            ->assertSee('Managed by the administrator.');
    }

    public function test_a_student_cannot_take_an_id_or_email_that_belongs_to_someone_else(): void
    {
        User::factory()->create(['employee_student_id' => '2401361', 'email' => 'taken@lnu.edu.ph']);
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->patch(route('profile.update'), [
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'employee_student_id' => '2401361',
                'email' => 'taken@lnu.edu.ph',
            ])
            ->assertSessionHasErrors(['employee_student_id', 'email']);
    }

    public function test_contact_numbers_must_be_phone_numbers(): void
    {
        $user = User::factory()->faculty()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), ['email' => $user->email, 'contact_number' => 'call me maybe'])
            ->assertSessionHasErrors('contact_number');
    }

    public function test_a_photo_is_saved_as_a_square_and_replaces_the_old_one(): void
    {
        $user = User::factory()->faculty()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), ['email' => $user->email, 'photo' => UploadedFile::fake()->image('me.png', 800, 600)])
            ->assertRedirect(route('profile.edit'));

        $firstPhoto = $user->fresh()->profile_photo;
        Storage::disk('local')->assertExists($firstPhoto);
        $this->assertSame([400, 400], array_slice(getimagesizefromstring(Storage::disk('local')->get($firstPhoto)), 0, 2));

        $this->actingAs($user)
            ->patch(route('profile.update'), ['email' => $user->email, 'photo' => UploadedFile::fake()->image('new.jpg', 500, 500)]);

        Storage::disk('local')->assertMissing($firstPhoto);
        Storage::disk('local')->assertExists($user->fresh()->profile_photo);
    }

    public function test_files_that_are_not_photos_are_rejected(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'employee_student_id' => $user->employee_student_id,
                'email' => $user->email,
                'photo' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->profile_photo);
    }

    public function test_photos_are_shown_only_to_signed_in_users_and_can_be_removed(): void
    {
        $user = User::factory()->student()->create(['profile_photo' => 'profile-photos/me.jpg']);
        Storage::disk('local')->put('profile-photos/me.jpg', 'jpeg bytes');

        $this->get(route('profile.photo', $user))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->faculty()->create())
            ->get(route('profile.photo', $user))
            ->assertOk();

        $this->actingAs($user)
            ->delete(route('profile.photo.destroy'))
            ->assertRedirect(route('profile.edit'));

        $this->assertNull($user->fresh()->profile_photo);
        Storage::disk('local')->assertMissing('profile-photos/me.jpg');
        $this->actingAs($user)->get(route('profile.photo', $user))->assertNotFound();
    }

    public function test_everyone_can_change_their_password_with_the_current_one(): void
    {
        $user = User::factory()->faculty()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->put(route('profile.password'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrorsIn('password', ['current_password' => 'Your current password is incorrect.']);

        $this->actingAs($user)
            ->put(route('profile.password'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHas('success', 'Your password has been changed.');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
