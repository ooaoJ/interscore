<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompetitionCategory;
use App\Models\Interclass;
use App\Models\Modality;
use App\Models\Sport;
use Illuminate\Http\Request;

class ModalityController extends Controller
{
    public function sports()
    {
        return response()->json([
            'sports' => Sport::where('status', 'active')
                ->orderBy('name')
                ->get()
        ]);
    }

    public function categories()
    {
        return response()->json([
            'categories' => CompetitionCategory::with('grades')
                ->orderBy('sort_order')
                ->get()
        ]);
    }

    public function index(Request $request, Interclass $interclass)
    {
        $this->authorizeInterclassAccess($request, $interclass);

        $modalities = Modality::with(['sport', 'competitionCategory', 'scores'])
            ->where('interclass_id', $interclass->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'modalities' => $modalities
        ]);
    }

    public function store(Request $request, Interclass $interclass)
    {
        $this->authorizeInterclassAccess($request, $interclass);

        $data = $request->validate([
            'sport_id' => 'required|exists:sports,id',
            'competition_category_id' => 'required|exists:competition_categories,id',
            'name' => 'required|string|max:160',
            'gender' => 'nullable|in:male,female,mixed,open',
            'team_type' => 'nullable|in:collective,individual',
            'min_athletes' => 'nullable|integer|min:1|max:99',
            'max_athletes' => 'nullable|integer|min:1|max:99',
            'allow_draw' => 'nullable|boolean',
            'has_third_place' => 'nullable|boolean',
            'bracket_type' => 'nullable|in:single_elimination,group_stage,round_robin',
            'status' => 'nullable|in:draft,open,bracket_generated,in_progress,finished,cancelled',
            'scores' => 'nullable|array',
            'scores.*.position' => 'required_with:scores|integer|min:1|max:3',
            'scores.*.points' => 'required_with:scores|numeric|min:0|max:9999',
        ]);

        if (($data['min_athletes'] ?? 1) > ($data['max_athletes'] ?? 20)) {
            return response()->json([
                'message' => 'O mínimo de atletas não pode ser maior que o máximo.'
            ], 422);
        }

        $exists = Modality::where('interclass_id', $interclass->id)
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Já existe uma modalidade com este nome neste interclasse.'
            ], 422);
        }

        $modality = Modality::create([
            'school_id' => $interclass->school_id,
            'interclass_id' => $interclass->id,
            'sport_id' => $data['sport_id'],
            'competition_category_id' => $data['competition_category_id'],
            'name' => $data['name'],
            'gender' => $data['gender'] ?? 'open',
            'team_type' => $data['team_type'] ?? 'collective',
            'min_athletes' => $data['min_athletes'] ?? 1,
            'max_athletes' => $data['max_athletes'] ?? 20,
            'allow_draw' => $data['allow_draw'] ?? false,
            'has_third_place' => $data['has_third_place'] ?? true,
            'bracket_type' => $data['bracket_type'] ?? 'single_elimination',
            'status' => $data['status'] ?? 'draft',
        ]);

        foreach (($data['scores'] ?? []) as $score) {
            $modality->scores()->create([
                'position' => $score['position'],
                'points' => $score['points'],
            ]);
        }

        return response()->json([
            'message' => 'Modalidade cadastrada com sucesso.',
            'modality' => $modality->load(['sport', 'competitionCategory', 'scores'])
        ], 201);
    }

    public function show(Request $request, Modality $modality)
    {
        $this->authorizeModalityAccess($request, $modality);

        return response()->json([
            'modality' => $modality->load([
                'sport',
                'competitionCategory',
                'scores',
                'teams',
                'moderators'
            ])
        ]);
    }

    public function update(Request $request, Modality $modality)
    {
        $this->authorizeModalityAccess($request, $modality);

        if (in_array($modality->status, ['bracket_generated', 'in_progress', 'finished'])) {
            return response()->json([
                'message' => 'Não é permitido editar uma modalidade com chaveamento gerado, em andamento ou finalizada.'
            ], 422);
        }

        $data = $request->validate([
            'sport_id' => 'sometimes|required|exists:sports,id',
            'competition_category_id' => 'sometimes|required|exists:competition_categories,id',
            'name' => 'sometimes|required|string|max:160',
            'gender' => 'sometimes|required|in:male,female,mixed,open',
            'team_type' => 'sometimes|required|in:collective,individual',
            'min_athletes' => 'sometimes|required|integer|min:1|max:99',
            'max_athletes' => 'sometimes|required|integer|min:1|max:99',
            'allow_draw' => 'nullable|boolean',
            'has_third_place' => 'nullable|boolean',
            'bracket_type' => 'sometimes|required|in:single_elimination,group_stage,round_robin',
            'status' => 'sometimes|required|in:draft,open,cancelled',
            'scores' => 'nullable|array',
            'scores.*.position' => 'required_with:scores|integer|min:1|max:3',
            'scores.*.points' => 'required_with:scores|numeric|min:0|max:9999',
        ]);

        $min = $data['min_athletes'] ?? $modality->min_athletes;
        $max = $data['max_athletes'] ?? $modality->max_athletes;

        if ($min > $max) {
            return response()->json([
                'message' => 'O mínimo de atletas não pode ser maior que o máximo.'
            ], 422);
        }

        if (isset($data['name']) && $data['name'] !== $modality->name) {
            $exists = Modality::where('interclass_id', $modality->interclass_id)
                ->where('name', $data['name'])
                ->where('id', '!=', $modality->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Já existe uma modalidade com este nome neste interclasse.'
                ], 422);
            }
        }

        $scores = $data['scores'] ?? null;
        unset($data['scores']);

        $modality->update($data);

        if ($scores !== null) {
            $modality->scores()->delete();

            foreach ($scores as $score) {
                $modality->scores()->create([
                    'position' => $score['position'],
                    'points' => $score['points'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Modalidade atualizada com sucesso.',
            'modality' => $modality->load(['sport', 'competitionCategory', 'scores'])
        ]);
    }

    public function destroy(Request $request, Modality $modality)
    {
        $this->authorizeModalityAccess($request, $modality);

        if ($modality->teams()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover uma modalidade que possui equipes cadastradas.'
            ], 422);
        }

        $modality->delete();

        return response()->json([
            'message' => 'Modalidade removida com sucesso.'
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
}