<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\User\StoreUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): Response {}

    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $this->userService->create($request->validated(), $request->file('photo'));
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['general' => 'Não foi possível cadastrar o usuário. Tente novamente.']);
        }

        return redirect()->route('groups.index')->with('success', 'Usuário cadastrado com sucesso.');
    }

    public function assignRole(User $user, string $role): RedirectResponse
    {
        if (! in_array($role, Role::values(), true)) {
            return back()->withErrors(['role' => 'A função informada não existe.']);
        }

        try {
            $user->assignRole($role);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['role' => 'Não foi possível atribuir a função ao usuário.']);
        }

        return back();
    }

    public function removeRole(User $user, string $role): RedirectResponse
    {
        if (! in_array($role, Role::values(), true)) {
            return back()->withErrors(['role' => 'A função informada não existe.']);
        }

        try {
            $user->removeRole($role);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['role' => 'Não foi possível remover a função do usuário.']);
        }

        return back();
    }
}
