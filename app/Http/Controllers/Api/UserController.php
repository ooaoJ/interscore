<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('school')
            ->when($request->school_id, function ($query) use ($request) {
                $query->where('school_id', $request->school_id);
            })
            ->when($request->role, function ($query) use ($request) {
                $query->where('role', $request->role);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:180|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:school_manager,moderator',
            'status' => 'nullable|in:active,inactive,blocked',
        ]);

        School::findOrFail($data['school_id']);

        $user = User::create([
            'school_id' => $data['school_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Usuário cadastrado com sucesso.',
            'user' => $user->load('school')
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load('school')
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->role === 'platform_admin') {
            return response()->json([
                'message' => 'Não é permitido editar o administrador da plataforma por esta rota.'
            ], 403);
        }

        $data = $request->validate([
            'school_id' => 'sometimes|required|exists:schools,id',
            'name' => 'sometimes|required|string|max:150',
            'email' => 'sometimes|required|email|max:180|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|required|in:school_manager,moderator',
            'status' => 'sometimes|required|in:active,inactive,blocked',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'user' => $user->load('school')
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->role === 'platform_admin') {
            return response()->json([
                'message' => 'Não é permitido remover o administrador da plataforma.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuário removido com sucesso.'
        ]);
    }
}