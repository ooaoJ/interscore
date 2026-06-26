<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Modality;
use App\Models\Student;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request, Modality $modality)
    {
        $this->authorizeModalityAccess($request, $modality);

        $teams = Team::with(['classroom.grade', 'students'])
            ->where('modality_id', $modality->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'teams' => $teams
        ]);
    }

    public function store(Request $request, Modality $modality)
    {
        $this->authorizeModalityAccess($request, $modality);

        if (!in_array($modality->status, ['draft', 'open'])) {
            return response()->json([
                'message' => 'Não é possível cadastrar equipe nesta modalidade.'
            ], 422);
        }

        $data = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'name' => 'nullable|string|max:160',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|exists:students,id',
        ]);

        $classroom = Classroom::findOrFail($data['classroom_id']);

        if ($classroom->interclass_id !== $modality->interclass_id) {
            return response()->json([
                'message' => 'A turma não pertence ao mesmo interclasse da modalidade.'
            ], 422);
        }

        if ($classroom->school_id !== $modality->school_id) {
            return response()->json([
                'message' => 'A turma não pertence à mesma escola da modalidade.'
            ], 422);
        }

        $exists = Team::where('modality_id', $modality->id)
            ->where('classroom_id', $classroom->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Esta turma já possui uma equipe nesta modalidade.'
            ], 422);
        }

        $students = Student::whereIn('id', $data['student_ids'])
            ->where('classroom_id', $classroom->id)
            ->where('status', 'active')
            ->get();

        if ($students->count() !== count(array_unique($data['student_ids']))) {
            return response()->json([
                'message' => 'Todos os alunos devem estar ativos e pertencer à turma selecionada.'
            ], 422);
        }

        if ($students->count() < $modality->min_athletes) {
            return response()->json([
                'message' => "A equipe precisa ter no mínimo {$modality->min_athletes} atleta(s)."
            ], 422);
        }

        if ($students->count() > $modality->max_athletes) {
            return response()->json([
                'message' => "A equipe pode ter no máximo {$modality->max_athletes} atleta(s)."
            ], 422);
        }

        $alreadyInModality = Student::whereIn('students.id', $data['student_ids'])
            ->whereHas('teamStudents', function ($query) use ($modality) {
                $query->where('modality_id', $modality->id);
            })
            ->exists();

        if ($alreadyInModality) {
            return response()->json([
                'message' => 'Um ou mais alunos já estão inscritos nesta modalidade.'
            ], 422);
        }

        $team = Team::create([
            'school_id' => $modality->school_id,
            'interclass_id' => $modality->interclass_id,
            'classroom_id' => $classroom->id,
            'modality_id' => $modality->id,
            'name' => $data['name'] ?? "{$classroom->name} - {$modality->name}",
            'status' => 'valid',
            'created_by' => $request->user()->id,
        ]);

        foreach ($students as $index => $student) {
            $team->students()->attach($student->id, [
                'interclass_id' => $modality->interclass_id,
                'modality_id' => $modality->id,
                'participation_type' => $index === 0 ? 'captain' : 'athlete',
            ]);
        }

        return response()->json([
            'message' => 'Equipe cadastrada com sucesso.',
            'team' => $team->load(['classroom.grade', 'modality', 'students'])
        ], 201);
    }

    public function show(Request $request, Team $team)
    {
        $this->authorizeTeamAccess($request, $team);

        return response()->json([
            'team' => $team->load([
                'school',
                'interclass',
                'classroom.grade',
                'modality.sport',
                'students'
            ])
        ]);
    }

    public function update(Request $request, Team $team)
    {
        $this->authorizeTeamAccess($request, $team);

        if (!in_array($team->modality->status, ['draft', 'open'])) {
            return response()->json([
                'message' => 'Não é possível editar equipe desta modalidade.'
            ], 422);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:160',
            'status' => 'sometimes|required|in:draft,valid,blocked',
            'student_ids' => 'nullable|array|min:1',
            'student_ids.*' => 'required_with:student_ids|exists:students,id',
        ]);

        if (isset($data['name'])) {
            $team->name = $data['name'];
        }

        if (isset($data['status'])) {
            $team->status = $data['status'];
        }

        $team->save();

        if (isset($data['student_ids'])) {
            $students = Student::whereIn('id', $data['student_ids'])
                ->where('classroom_id', $team->classroom_id)
                ->where('status', 'active')
                ->get();

            if ($students->count() !== count(array_unique($data['student_ids']))) {
                return response()->json([
                    'message' => 'Todos os alunos devem estar ativos e pertencer à turma da equipe.'
                ], 422);
            }

            if ($students->count() < $team->modality->min_athletes) {
                return response()->json([
                    'message' => "A equipe precisa ter no mínimo {$team->modality->min_athletes} atleta(s)."
                ], 422);
            }

            if ($students->count() > $team->modality->max_athletes) {
                return response()->json([
                    'message' => "A equipe pode ter no máximo {$team->modality->max_athletes} atleta(s)."
                ], 422);
            }

            $alreadyInOtherTeam = Student::whereIn('students.id', $data['student_ids'])
                ->whereHas('teamStudents', function ($query) use ($team) {
                    $query->where('modality_id', $team->modality_id)
                        ->where('team_id', '!=', $team->id);
                })
                ->exists();

            if ($alreadyInOtherTeam) {
                return response()->json([
                    'message' => 'Um ou mais alunos já estão em outra equipe desta modalidade.'
                ], 422);
            }

            $syncData = [];

            foreach ($students as $index => $student) {
                $syncData[$student->id] = [
                    'interclass_id' => $team->interclass_id,
                    'modality_id' => $team->modality_id,
                    'participation_type' => $index === 0 ? 'captain' : 'athlete',
                ];
            }

            $team->students()->sync($syncData);
        }

        return response()->json([
            'message' => 'Equipe atualizada com sucesso.',
            'team' => $team->load(['classroom.grade', 'modality', 'students'])
        ]);
    }

    public function destroy(Request $request, Team $team)
    {
        $this->authorizeTeamAccess($request, $team);

        if ($team->matchesAsTeamA()->exists() || $team->matchesAsTeamB()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover uma equipe que já possui partidas.'
            ], 422);
        }

        $team->students()->detach();
        $team->delete();

        return response()->json([
            'message' => 'Equipe removida com sucesso.'
        ]);
    }

    private function authorizeModalityAccess(Request $request, Modality $modality): void
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            return;
        }

        if ($user->school_id !== $modality->school_id) {
            abort(403, 'Você não tem permissão para acessar esta modalidade.');
        }
    }

    private function authorizeTeamAccess(Request $request, Team $team): void
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            return;
        }

        if ($user->school_id !== $team->school_id) {
            abort(403, 'Você não tem permissão para acessar esta equipe.');
        }
    }
}