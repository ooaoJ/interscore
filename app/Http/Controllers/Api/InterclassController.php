<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interclass;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InterclassController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $interclasses = Interclass::with(['school', 'creator'])
            ->when(!$user->isPlatformAdmin(), function ($query) use ($user) {
                $query->where('school_id', $user->school_id);
            })
            ->when($request->school_id && $user->isPlatformAdmin(), function ($query) use ($request) {
                $query->where('school_id', $request->school_id);
            })
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();

        return response()->json([
            'interclasses' => $interclasses
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => 'required|string|max:160',
            'year' => 'required|integer|min:2000|max:2100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'regulation' => 'nullable|string',
            'banner_path' => 'nullable|string|max:255',
            'visibility' => 'nullable|in:private,public',
            'status' => 'nullable|in:draft,registration_open,bracket_generated,in_progress,finished,archived,cancelled',
        ];

        if ($user->isPlatformAdmin()) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        $schoolId = $user->isPlatformAdmin()
            ? $data['school_id']
            : $user->school_id;

        if (!$schoolId) {
            return response()->json([
                'message' => 'Usuário não está vinculado a uma escola.'
            ], 422);
        }

        $school = School::findOrFail($schoolId);

        if ($school->status !== 'active') {
            return response()->json([
                'message' => 'Não é possível criar interclasse para uma escola inativa ou bloqueada.'
            ], 422);
        }

        $slug = $this->makeUniqueSlug($data['name'], $schoolId);

        $interclass = Interclass::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'slug' => $slug,
            'year' => $data['year'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'description' => $data['description'] ?? null,
            'regulation' => $data['regulation'] ?? null,
            'banner_path' => $data['banner_path'] ?? null,
            'visibility' => $data['visibility'] ?? 'private',
            'status' => $data['status'] ?? 'draft',
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Interclasse cadastrado com sucesso.',
            'interclass' => $interclass->load(['school', 'creator'])
        ], 201);
    }

    public function show(Request $request, Interclass $interclass)
    {
        $this->authorizeInterclassAccess($request, $interclass);

        return response()->json([
            'interclass' => $interclass->load([
                'school',
                'creator',
                'classrooms',
                'modalities',
                'teams'
            ])
        ]);
    }

    public function update(Request $request, Interclass $interclass)
    {
        $this->authorizeInterclassAccess($request, $interclass);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:160',
            'year' => 'sometimes|required|integer|min:2000|max:2100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'regulation' => 'nullable|string',
            'banner_path' => 'nullable|string|max:255',
            'visibility' => 'nullable|in:private,public',
            'status' => 'nullable|in:draft,registration_open,bracket_generated,in_progress,finished,archived,cancelled',
        ]);

        if (isset($data['name']) && $data['name'] !== $interclass->name) {
            $data['slug'] = $this->makeUniqueSlug(
                $data['name'],
                $interclass->school_id,
                $interclass->id
            );
        }

        $interclass->update($data);

        return response()->json([
            'message' => 'Interclasse atualizado com sucesso.',
            'interclass' => $interclass->load(['school', 'creator'])
        ]);
    }

    public function destroy(Request $request, Interclass $interclass)
    {
        $this->authorizeInterclassAccess($request, $interclass);

        if (in_array($interclass->status, ['in_progress', 'finished'])) {
            return response()->json([
                'message' => 'Não é permitido remover um interclasse em andamento ou finalizado.'
            ], 422);
        }

        $interclass->delete();

        return response()->json([
            'message' => 'Interclasse removido com sucesso.'
        ]);
    }

    private function authorizeInterclassAccess(Request $request, Interclass $interclass): void
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            return;
        }

        if ($user->school_id !== $interclass->school_id) {
            abort(403, 'Você não tem permissão para acessar este interclasse.');
        }
    }

    private function makeUniqueSlug(string $name, int $schoolId, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Interclass::where('school_id', $schoolId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}