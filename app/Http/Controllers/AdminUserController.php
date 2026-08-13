<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->with(['roles', 'accountOwner'])
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'account' => $user->load('roles'),
            'roles' => $this->roles(),
            'accountOwners' => $this->accountOwners($user),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => $this->roles(),
            'accountOwners' => $this->accountOwners(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'account_owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $roles = $this->validatedRoles($data);

        if ($error = $this->validateAccountOwner($roles, $data['account_owner_id'] ?? null)) {
            return back()
                ->withErrors(['account_owner_id' => $error])
                ->withInput();
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'first_login' => true,
            'account_owner_id' => $roles->contains('usuario_subordinado') ? ($data['account_owner_id'] ?? null) : null,
        ]);

        $user->syncRoles($roles->all());

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'user-created');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'account_owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $user->id))],
        ]);

        $roles = $this->validatedRoles($data);

        if ($user->hasRole('admin') && ! $roles->contains('admin') && $this->adminCount() <= 1) {
            return back()
                ->withErrors(['roles' => 'No puedes quitar el rol admin al unico administrador del sistema.'])
                ->withInput();
        }

        if ($error = $this->validateAccountOwner($roles, $data['account_owner_id'] ?? null)) {
            return back()
                ->withErrors(['account_owner_id' => $error])
                ->withInput();
        }

        $userData = [
            'account_owner_id' => $roles->contains('usuario_subordinado') ? ($data['account_owner_id'] ?? null) : null,
        ];

        if (array_key_exists('name', $data)) {
            $userData['name'] = $data['name'];
        }

        if (array_key_exists('email', $data)) {
            $userData['email'] = $data['email'];
        }

        if (filled($data['password'] ?? null)) {
            $userData['password'] = Hash::make($data['password']);
            $userData['first_login'] = true;
        }

        $user->forceFill($userData)->save();

        $user->syncRoles($roles->all());

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'user-updated');
    }

    private function adminCount(): int
    {
        return User::role('admin')->count();
    }

    private function roles()
    {
        return Role::query()->orderBy('name')->get();
    }

    private function accountOwners(?User $excludedUser = null)
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', User::ACCOUNT_OWNER_ROLES))
            ->when($excludedUser, fn ($query) => $query->whereKeyNot($excludedUser->id))
            ->orderBy('name')
            ->get();
    }

    private function validatedRoles(array $data)
    {
        return collect($data['roles'] ?? [])
            ->filter()
            ->unique()
            ->values();
    }

    private function validateAccountOwner($roles, $accountOwnerId): ?string
    {
        if (! $roles->contains('usuario_subordinado') || blank($accountOwnerId)) {
            return null;
        }

        $owner = User::find($accountOwnerId);

        if ($owner?->isAccountOwner()) {
            return null;
        }

        return 'Selecciona un usuario con rol jefe de cuenta como jefe de cuenta.';
    }
}
