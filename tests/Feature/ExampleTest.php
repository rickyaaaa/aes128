<?php

namespace Tests\Feature;

use App\Models\FileLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

    public function test_login_form_uses_username_instead_of_email(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Username')
            ->assertDontSee('Email');
    }

    public function test_login_redirects_by_role(): void
    {
        User::create([
            'name' => 'Owner',
            'username' => 'owner',
            'password' => 'password',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'username' => 'owner',
            'password' => 'password',
        ])->assertRedirect(route('owner.dashboard'));
    }

    public function test_role_middleware_blocks_staff_from_owner_pages(): void
    {
        $staff = User::create([
            'name' => 'Staff',
            'username' => 'staff',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get('/owner/dashboard')
            ->assertForbidden();
    }

    public function test_authenticated_user_can_open_about_page(): void
    {
        $staff = $this->staffUser();

        $this->actingAs($staff)
            ->get(route('about'))
            ->assertOk()
            ->assertSee('Tentang Aplikasi')
            ->assertSeeInOrder([
                'Tentang Aplikasi Ini',
                'Fitur Terkini',
                'Alur Kerja Sistem',
                'Teknologi Terkini',
                'Spesifikasi Keamanan',
                'Panduan Cepat',
            ]);
    }

    public function test_owner_history_replaces_redundant_global_audit_page(): void
    {
        $owner = $this->ownerUser();

        $this->actingAs($owner)
            ->get(route('history'))
            ->assertOk()
            ->assertSee('Riwayat File Sistem')
            ->assertDontSee('Audit Global');
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
        $log->forceFill(['created_at' => Carbon::parse('2026-05-31 16:51:00', 'UTC')])->saveQuietly();

        $this->actingAs($staff)
            ->get(route('files.show', $log))
            ->assertOk()
            ->assertSee('Detail File')
            ->assertSee('invoice.pdf')
            ->assertSee('31 May 2026, 23:51')
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
            'username' => 'owner',
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
            'username' => 'other_staff',
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

    public function test_local_date_scope_uses_jakarta_day_boundaries(): void
    {
        $staff = $this->staffUser();

        DB::table('file_logs')->insert([
            [
                'user_id' => $staff->id,
                'file_name' => 'before-midnight.pdf',
                'stored_path' => 'encrypted/before-midnight.enc',
                'file_size' => 100,
                'file_type' => 'pdf',
                'created_at' => '2026-05-31 16:59:00',
                'updated_at' => '2026-05-31 16:59:00',
            ],
            [
                'user_id' => $staff->id,
                'file_name' => 'after-midnight.pdf',
                'stored_path' => 'encrypted/after-midnight.enc',
                'file_size' => 100,
                'file_type' => 'pdf',
                'created_at' => '2026-05-31 17:00:00',
                'updated_at' => '2026-05-31 17:00:00',
            ],
        ]);

        $this->assertSame(
            ['after-midnight.pdf'],
            FileLog::createdDuringLocalDate('2026-06-01')->pluck('file_name')->all(),
        );
    }

    public function test_owner_can_create_staff_account(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'username' => 'owner',
            'password' => 'password',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post('/owner/users', [
                'name' => 'New Staff',
                'username' => 'new_staff',
                'password' => 'password',
                'is_active' => '1',
            ])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'username' => 'new_staff',
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    private function staffUser(): User
    {
        return User::create([
            'name' => 'Staff',
            'username' => 'staff',
            'password' => 'password',
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    private function ownerUser(): User
    {
        return User::create([
            'name' => 'Owner',
            'username' => 'owner',
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
