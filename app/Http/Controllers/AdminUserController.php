<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
            'roles' => Role::query()->orderBy('name')->get(),
            'accountOwners' => User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('name', User::ACCOUNT_OWNER_ROLES))
                ->whereKeyNot($user->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'account_owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $user->id))],
        ]);

        $roles = collect($data['roles'] ?? [])
            ->filter()
            ->unique()
            ->values();

        if ($user->hasRole('admin') && ! $roles->contains('admin') && $this->adminCount() <= 1) {
            return back()
                ->withErrors(['roles' => 'No puedes quitar el rol admin al unico administrador del sistema.'])
                ->withInput();
        }

        if ($roles->contains('usuario_subordinado') && filled($data['account_owner_id'] ?? null)) {
            $owner = User::find($data['account_owner_id']);

            if (! $owner?->isAccountOwner()) {
                return back()
                    ->withErrors(['account_owner_id' => 'Selecciona un usuario con rol jefe de cuenta como jefe de cuenta.'])
                    ->withInput();
            }
        }

        $user->forceFill([
            'account_owner_id' => $roles->contains('usuario_subordinado') ? ($data['account_owner_id'] ?? null) : null,
        ])->save();

        $user->syncRoles($roles->all());

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'user-roles-saved');
    }

    private function adminCount(): int
    {
        return User::role('admin')->count();
    }
}
