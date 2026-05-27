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

    public function test_staff_can_encrypt_file_and_download_stored_enc(): void
    {
        Storage::fake('local');
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->post('/encrypt', [
                'source_file' => UploadedFile::fake()->image('invoice.png')->size(512),
                'secret_key' => 'secret123',
            ])
            ->assertSessionHas('status')
            ->assertSessionHas('output_filename', 'invoice.png.enc');

        $log = FileLog::firstOrFail();

        $this->assertDatabaseHas('file_logs', [
            'user_id' => $staff->id,
            'original_filename' => 'invoice.png',
            'file_type' => 'png',
            'process_type' => FileLog::PROCESS_ENCRYPTION,
            'status' => FileLog::STATUS_SUCCESS,
        ]);

        Storage::disk('local')->assertExists($log->stored_path);

        $this->actingAs($staff)
            ->get(route('files.download', $log))
            ->assertOk();
    }

    public function test_staff_can_decrypt_file_with_correct_key(): void
    {
        Storage::fake('local');
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->actingAs($staff)->post('/encrypt', [
            'source_file' => UploadedFile::fake()->createWithContent('invoice.pdf', 'PDF content')->size(8),
            'secret_key' => 'secret123',
        ]);

        $encryptedLog = FileLog::where('process_type', FileLog::PROCESS_ENCRYPTION)->firstOrFail();
        $encryptedUpload = new UploadedFile(
            Storage::disk('local')->path($encryptedLog->stored_path),
            $encryptedLog->output_filename,
            'application/octet-stream',
            null,
            true,
        );

        $this->actingAs($staff)
            ->post('/decrypt', [
                'encrypted_file' => $encryptedUpload,
                'secret_key' => 'secret123',
            ])
            ->assertSessionHas('status')
            ->assertSessionHas('output_filename', 'invoice.pdf');

        $decryptLog = FileLog::where('process_type', FileLog::PROCESS_DECRYPTION)->firstOrFail();

        $this->assertSame(FileLog::STATUS_SUCCESS, $decryptLog->status);
        Storage::disk('local')->assertExists($decryptLog->stored_path);
    }

    public function test_wrong_secret_key_is_rejected_and_logged(): void
    {
        Storage::fake('local');
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->actingAs($staff)->post('/encrypt', [
            'source_file' => UploadedFile::fake()->createWithContent('invoice.pdf', 'PDF content')->size(8),
            'secret_key' => 'secret123',
        ]);

        $encryptedLog = FileLog::where('process_type', FileLog::PROCESS_ENCRYPTION)->firstOrFail();
        $encryptedUpload = new UploadedFile(
            Storage::disk('local')->path($encryptedLog->stored_path),
            $encryptedLog->output_filename,
            'application/octet-stream',
            null,
            true,
        );

        $this->actingAs($staff)
            ->post('/decrypt', [
                'encrypted_file' => $encryptedUpload,
                'secret_key' => 'wrongkey123',
            ])
            ->assertSessionHasErrors('encrypted_file');

        $this->assertDatabaseHas('file_logs', [
            'user_id' => $staff->id,
            'process_type' => FileLog::PROCESS_DECRYPTION,
            'status' => FileLog::STATUS_FAILED,
        ]);
    }

    public function test_upload_validation_limits_file_types(): void
    {
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.test',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->post('/encrypt', [
                'source_file' => UploadedFile::fake()->create('notes.txt', 2, 'text/plain'),
                'secret_key' => 'secret123',
            ])
            ->assertSessionHasErrors('source_file');

        $this->actingAs($staff)
            ->post('/decrypt', [
                'encrypted_file' => UploadedFile::fake()->create('invoice.pdf', 2, 'application/pdf'),
                'secret_key' => 'secret123',
            ])
            ->assertSessionHasErrors('encrypted_file');
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
}
