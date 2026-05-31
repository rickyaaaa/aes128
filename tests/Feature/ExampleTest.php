<?php

namespace Tests\Feature;

use App\Models\FileLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_home_redirects_guest_to_login(): void
    {
        $this->get('/')
            ->assertRedirect('/login');
    }

    public function test_login_redirects_by_role(): void
    {
        User::create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'password',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => 'owner@example.test',
            'password' => 'password',
        ])->assertRedirect(route('owner.dashboard'));
    }

    public function test_role_middleware_blocks_staff_from_owner_pages(): void
    {
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get('/owner/dashboard')
            ->assertForbidden();
    }

    public function test_staff_can_encrypt_file_and_record_file_repository_row(): void
    {
        Storage::fake('local');
        $staff = $this->staffUser();

        $response = $this->actingAs($staff)
            ->post('/encrypt', [
                'source_file' => UploadedFile::fake()->image('invoice.png')->size(512),
                'secret_key' => 'secret123',
            ])
            ->assertSessionHas('status')
            ->assertSessionHas('output_filename', 'invoice.png.enc');

        $log = FileLog::firstOrFail();

        $this->assertDatabaseHas('file_logs', [
            'id' => $log->id,
            'user_id' => $staff->id,
            'file_name' => 'invoice.png',
            'file_type' => 'png',
        ]);
        Storage::disk('local')->assertExists($log->stored_path);
        $this->assertStringEndsWith('.enc', $log->stored_path);
        $response->assertRedirect(route('files.show', $log));
    }

    public function test_detail_page_shows_metadata_without_downloading_the_file(): void
    {
        Storage::fake('local');
        $staff = $this->staffUser();
        $log = $this->encryptFileFor($staff, 'invoice.pdf', 'PDF content', 'secret123');

        $this->actingAs($staff)
            ->get(route('files.show', $log))
            ->assertOk()
            ->assertSee('Detail File')
            ->assertSee('invoice.pdf')
            ->assertSee('Dekripsi & Download', false);
    }

    public function test_staff_can_decrypt_by_file_id_and_auto_download(): void
    {
        Storage::fake('local');
        $staff = $this->staffUser();
        $log = $this->encryptFileFor($staff, 'invoice.pdf', 'PDF content', 'secret123');

        $this->actingAs($staff)
            ->post(route('files.decrypt', $log), [
                'secret_key' => 'secret123',
            ])
            ->assertOk()
            ->assertDownload('invoice.pdf');
    }

    public function test_wrong_password_returns_invalid_password_error(): void
    {
        Storage::fake('local');
        $staff = $this->staffUser();
        $log = $this->encryptFileFor($staff, 'invoice.pdf', 'PDF content', 'secret123');

        $this->actingAs($staff)
            ->post(route('files.decrypt', $log), [
                'secret_key' => 'wrongpass123',
            ])
            ->assertSessionHasErrors('decrypt_secret_key');
    }

    public function test_staff_can_update_password_with_old_password_validation(): void
    {
        Storage::fake('local');
        $staff = $this->staffUser();
        $log = $this->encryptFileFor($staff, 'invoice.pdf', 'PDF content', 'secret123');

        $this->actingAs($staff)
            ->post(route('files.password.update', $log), [
                'old_secret_key' => 'secret123',
                'new_secret_key' => 'newsecret123',
            ])
            ->assertSessionHas('status');

        $this->actingAs($staff)
            ->post(route('files.decrypt', $log), [
                'secret_key' => 'secret123',
            ])
            ->assertSessionHasErrors('decrypt_secret_key');

        $this->actingAs($staff)
            ->post(route('files.decrypt', $log), [
                'secret_key' => 'newsecret123',
            ])
            ->assertOk()
            ->assertDownload('invoice.pdf');
    }

    public function test_owner_cannot_update_password_of_other_user_file(): void
    {
        Storage::fake('local');
        $staff = $this->staffUser();
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'password',
            'role' => 'owner',
            'is_active' => true,
        ]);
        $log = $this->encryptFileFor($staff, 'invoice.pdf', 'PDF content', 'secret123');

        $this->actingAs($owner)
            ->post(route('files.password.update', $log), [
                'old_secret_key' => 'secret123',
                'new_secret_key' => 'newsecret123',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_decrypt_staff_file_from_repository(): void
    {
        Storage::fake('local');
        $staff = $this->staffUser();
        $owner = $this->ownerUser();
        $log = $this->encryptFileFor($staff, 'invoice.pdf', 'PDF content', 'secret123');

        $this->actingAs($owner)
            ->get(route('files.show', $log))
            ->assertOk()
            ->assertSee('invoice.pdf');

        $this->actingAs($owner)
            ->post(route('files.decrypt', $log), [
                'secret_key' => 'secret123',
            ])
            ->assertOk()
            ->assertDownload('invoice.pdf');
    }

    public function test_staff_cannot_open_another_staff_file(): void
    {
        Storage::fake('local');
        $staff = $this->staffUser();
        $otherStaff = User::create([
            'name' => 'Other Staff',
            'email' => 'other.staff@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);
        $log = $this->encryptFileFor($staff, 'invoice.pdf', 'PDF content', 'secret123');

        $this->actingAs($otherStaff)
            ->get(route('files.show', $log))
            ->assertForbidden();
    }

    public function test_upload_validation_limits_file_types_to_jpg_png_pdf(): void
    {
        $staff = $this->staffUser();

        $this->actingAs($staff)
            ->post('/encrypt', [
                'source_file' => UploadedFile::fake()->create('notes.txt', 2, 'text/plain'),
                'secret_key' => 'secret123',
            ])
            ->assertSessionHasErrors('source_file');
    }

    public function test_owner_can_create_staff_account(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'password',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post('/owner/users', [
                'name' => 'New Staff',
                'email' => 'new.staff@example.test',
                'password' => 'password',
                'is_active' => '1',
            ])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'email' => 'new.staff@example.test',
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    private function staffUser(): User
    {
        return User::create([
            'name' => 'Staff',
            'email' => 'staff@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    private function ownerUser(): User
    {
        return User::create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'password',
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    private function encryptFileFor(User $user, string $filename, string $content, string $secretKey): FileLog
    {
        $this->actingAs($user)
            ->post('/encrypt', [
                'source_file' => UploadedFile::fake()->createWithContent($filename, $content),
                'secret_key' => $secretKey,
            ]);

        return FileLog::latest('id')->firstOrFail();
    }
}
