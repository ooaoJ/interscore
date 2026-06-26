<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroomAccess($request, $classroom);

        $students = Student::where('classroom_id', $classroom->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'students' => $students
        ]);
    }

    public function store(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroomAccess($request, $classroom);

        $data = $request->validate([
            'name' => 'required|string|max:160',
            'display_name' => 'nullable|string|max:80',
            'birth_date' => 'nullable|date',
            'gender' => 'required|in:M,F,O,N',
            'school_identifier' => 'required|string|max:60',
            'status' => 'nullable|in:active,inactive,transferred',
        ]);

        $exists = Student::where('interclass_id', $classroom->interclass_id)
            ->where('school_identifier', $data['school_identifier'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Já existe um aluno com este identificador neste interclasse.'
            ], 422);
        }

        $student = Student::create([
            'school_id' => $classroom->school_id,
            'interclass_id' => $classroom->interclass_id,
            'classroom_id' => $classroom->id,
            'name' => $data['name'],
            'display_name' => $data['display_name'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'],
            'school_identifier' => $data['school_identifier'],
            'status' => $data['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Aluno cadastrado com sucesso.',
            'student' => $student
        ], 201);
    }

    public function show(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($request, $student);

        return response()->json([
            'student' => $student->load(['school', 'interclass', 'classroom'])
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($request, $student);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:160',
            'display_name' => 'nullable|string|max:80',
            'birth_date' => 'nullable|date',
            'gender' => 'sometimes|required|in:M,F,O,N',
            'school_identifier' => 'sometimes|required|string|max:60',
            'status' => 'sometimes|required|in:active,inactive,transferred',
        ]);

        if (
            isset($data['school_identifier']) &&
            $data['school_identifier'] !== $student->school_identifier
        ) {
            $exists = Student::where('interclass_id', $student->interclass_id)
                ->where('school_identifier', $data['school_identifier'])
                ->where('id', '!=', $student->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Já existe um aluno com este identificador neste interclasse.'
                ], 422);
            }
        }

        $student->update($data);

        return response()->json([
            'message' => 'Aluno atualizado com sucesso.',
            'student' => $student->load(['school', 'interclass', 'classroom'])
        ]);
    }

    public function destroy(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($request, $student);

        if ($student->teams()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover um aluno vinculado a uma equipe.'
            ], 422);
        }

        $student->delete();

        return response()->json([
            'message' => 'Aluno removido com sucesso.'
        ]);
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

    private function authorizeStudentAccess(Request $request, Student $student): void
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            return;
        }

        if ($user->school_id !== $student->school_id) {
            abort(403, 'Você não tem permissão para acessar este aluno.');
        }
    }
}