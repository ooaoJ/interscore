<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::with('settings')
            ->orderBy('name')
            ->get();

        return response()->json([
            'schools' => $schools
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:160',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'logo_path' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,blocked',
        ]);

        $data['slug'] = $this->makeUniqueSlug($data['name']);
        $data['status'] = $data['status'] ?? 'active';

        $school = School::create($data);

        $school->settings()->create([
            'show_student_full_name_public' => false,
            'show_student_identifier_public' => false,
            'allow_public_history' => true,
            'allow_public_team_students' => true,
        ]);

        return response()->json([
            'message' => 'Escola cadastrada com sucesso.',
            'school' => $school->load('settings')
        ], 201);
    }

    public function show(School $school)
    {
        return response()->json([
            'school' => $school->load('settings')
        ]);
    }

    public function update(Request $request, School $school)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'email' => 'nullable|email|max:160',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'logo_path' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,blocked',
        ]);

        if (isset($data['name']) && $data['name'] !== $school->name) {
            $data['slug'] = $this->makeUniqueSlug($data['name'], $school->id);
        }

        $school->update($data);

        return response()->json([
            'message' => 'Escola atualizada com sucesso.',
            'school' => $school->load('settings')
        ]);
    }

    public function destroy(School $school)
    {
        $school->delete();

        return response()->json([
            'message' => 'Escola removida com sucesso.'
        ]);
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
        School::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
