<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Interclass;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index(Request $request, Interclass $interclass)
    {
        $this->authorizeInterclassAccess($request, $interclass);

        $classrooms = Classroom::with(['grade', 'interclass'])
            ->where('interclass_id', $interclass->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'classrooms' => $classrooms
        ]);
    }

    public function store(Request $request, Interclass $interclass)
    {
        $this->authorizeInterclassAccess($request, $interclass);

        $data = $request->validate([
            'grade_id' => 'required|exists:grades,id',
            'name' => 'required|string|max:80',
            'shift' => 'nullable|in:morning,afternoon,evening,full_time',
            'course_name' => 'nullable|string|max:120',
            'status' => 'nullable|in:active,inactive',
        ]);

        $exists = Classroom::where('interclass_id', $interclass->id)
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Já existe uma turma com este nome neste interclasse.'
            ], 422);
        }

        $classroom = Classroom::create([
            'school_id' => $interclass->school_id,
            'interclass_id' => $interclass->id,
            'grade_id' => $data['grade_id'],
            'name' => $data['name'],
            'shift' => $data['shift'] ?? null,
            'course_name' => $data['course_name'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Turma cadastrada com sucesso.',
            'classroom' => $classroom->load(['grade', 'interclass'])
        ], 201);
    }

    public function show(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroomAccess($request, $classroom);

        return response()->json([
            'classroom' => $classroom->load(['school', 'interclass', 'grade', 'students'])
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroomAccess($request, $classroom);

        $data = $request->validate([
            'grade_id' => 'sometimes|required|exists:grades,id',
            'name' => 'sometimes|required|string|max:80',
            'shift' => 'nullable|in:morning,afternoon,evening,full_time',
            'course_name' => 'nullable|string|max:120',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (isset($data['name']) && $data['name'] !== $classroom->name) {
            $exists = Classroom::where('interclass_id', $classroom->interclass_id)
                ->where('name', $data['name'])
                ->where('id', '!=', $classroom->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Já existe uma turma com este nome neste interclasse.'
                ], 422);
            }
        }

        $classroom->update($data);

        return response()->json([
            'message' => 'Turma atualizada com sucesso.',
            'classroom' => $classroom->load(['grade', 'interclass'])
        ]);
    }

    public function destroy(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroomAccess($request, $classroom);

        if ($classroom->students()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover uma turma que possui alunos cadastrados.'
            ], 422);
        }

        if ($classroom->teams()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover uma turma que possui equipes cadastradas.'
            ], 422);
        }

        $classroom->delete();

        return response()->json([
            'message' => 'Turma removida com sucesso.'
        ]);
    }

    public function grades()
    {
        $grades = Grade::orderBy('sort_order')->get();

        return response()->json([
            'grades' => $grades
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

    private function authorizeClassroomAccess(Request $request, Classroom $classroom): void
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            return;
        }

        if ($user->school_id !== $classroom->school_id) {
            abort(403, 'Você não tem permissão para acessar esta turma.');
        }
    }
}