<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('owner.users', [
            'role' => 'owner',
            'pageTitle' => 'Manajemen User',
            'pageDescription' => 'CRUD akun Staff yang hanya dapat diakses oleh Owner.',
            'users' => User::where('role', 'staff')
                ->latest()
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'role' => $user->role,
                    'status' => $user->is_active ? 'Aktif' : 'Nonaktif',
                    'last_seen' => $user->updated_at?->format('d M Y, H:i') ?? '-',
                ])
                ->all(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        User::create([
            'name' => $request->string('name')->toString(),
            'username' => $request->string('username')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => 'staff',
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Akun Staff berhasil dibuat.');
    }

    public function update(UpdateStaffRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $data = [
            'name' => $request->string('name')->toString(),
            'username' => $request->string('username')->toString(),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->string('password')->toString();
        }

        $user->update($data);

        return back()->with('status', 'Akun Staff berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $user->delete();

        return back()->with('status', 'Akun Staff berhasil dihapus.');
    }
}
