<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interclass;
use App\Models\Modality;
use App\Models\ModeratorModality;
use App\Models\User;
use Illuminate\Http\Request;

class ModeratorController extends Controller
{
    public function index(Request $request, Interclass $interclass)
    {
        $this->authorizeInterclassAccess($request, $interclass);

        $moderators = ModeratorModality::with(['user', 'modality.sport', 'creator'])
            ->where('interclass_id', $interclass->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'moderators' => $moderators
        ]);
    }

    public function store(Request $request, Modality $modality)
    {
        $this->authorizeModalityAccess($request, $modality);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($data['user_id']);

        if ($user->role !== 'moderator') {
            return response()->json([
                'message' => 'O usuário selecionado não possui cargo de moderador.'
            ], 422);
        }

        if ($user->school_id !== $modality->school_id) {
            return response()->json([
                'message' => 'O moderador precisa pertencer à mesma escola da modalidade.'
            ], 422);
        }

        $alreadyAssigned = ModeratorModality::where('user_id', $user->id)
            ->where('modality_id', $modality->id)
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'message' => 'Este moderador já está designado para esta modalidade.'
            ], 422);
        }

        $assignment = ModeratorModality::create([
            'user_id' => $user->id,
            'interclass_id' => $modality->interclass_id,
            'modality_id' => $modality->id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Moderador designado com sucesso.',
            'moderator' => $assignment->load(['user', 'modality.sport', 'creator'])
        ], 201);
    }

    public function destroy(Request $request, ModeratorModality $moderatorModality)
    {
        $this->authorizeModalityAccess($request, $moderatorModality->modality);

        $moderatorModality->delete();

        return response()->json([
            'message' => 'Moderador removido da modalidade com sucesso.'
        ]);
    }

    public function myModalities(Request $request)
    {
        $user = $request->user();

        if (!$user->isModerator()) {
            return response()->json([
                'message' => 'Apenas moderadores possuem modalidades designadas.'
            ], 403);
        }

        $modalities = ModeratorModality::with(['modality.sport', 'modality.interclass'])
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'modalities' => $modalities
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