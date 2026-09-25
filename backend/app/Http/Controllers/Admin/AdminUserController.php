<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.admins', ['admins' => Admin::orderBy('id')->get(), 'roles' => AdminRole::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(AdminRole::class)],
        ]);

        Admin::create($data);

        return back()->with('success', 'Yönetici eklendi.');
    }

    public function destroy(Request $request, Admin $admin): RedirectResponse
    {
        if ($admin->is($request->user('admin'))) {
            return back()->with('error', 'Kendi hesabını silemezsin.');
        }
        $admin->delete();

        return back()->with('success', 'Yönetici silindi.');
    }
}
