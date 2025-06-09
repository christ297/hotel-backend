<?php

namespace App\Http\Controllers;

use App\Models\Chambre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChambreController extends Controller
{


    public function index()
    {
        try {
            $chambres = Chambre::all();
            return response()->json(['chambres' => $chambres], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la récupération des chambres'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            if (!Auth::user()->is_admin) {
                return response()->json(['message' => 'Action non autorisée'], 403);
            }

            $validatedData = $request->validate([
                'numero_chambre' => 'required|integer|unique:chambres',
                'type' => 'required|in:Simple,Double,Suite',
                'prix_nuite' => 'required|numeric|min:0',
                'disponibilite' => 'required|boolean',
                'description' => 'nullable|string|max:2500',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('photos', 'public');
                $validatedData['photo'] = $photoPath;
            }

            $chambre = Chambre::create($validatedData);
            return response()->json([
                'message' => 'Chambre créée avec succès',
                'chambre' => $chambre
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la chambre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            if (!Auth::user()->is_admin) {
                return response()->json(['message' => 'Action non autorisée'], 403);
            }

            $chambre = Chambre::findOrFail($id);

            $validatedData = $request->validate([
                'numero_chambre' => 'integer|unique:chambres,numero_chambre,' . $id,
                'type' => 'in:Simple,Double,Suite',
                'prix_nuite' => 'numeric|min:0',
                'disponibilite' => 'boolean',
                'description' => 'nullable|string|max:2500',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($request->hasFile('photo')) {
                if ($chambre->photo) {
                    Storage::disk('public')->delete($chambre->photo);
                }
                $photoPath = $request->file('photo')->store('photos', 'public');
                $validatedData['photo'] = $photoPath;
            }

            if ($request->has('numero_chambre')) {
            $chambre->numero_chambre = $validatedData['numero_chambre'];
            }
            if ($request->has('type')) {
                $chambre->type = $validatedData['type'];
            }
            if ($request->has('prix_nuite')) {
                $chambre->prix_nuite = $validatedData['prix_nuite'];
            }
            if ($request->has('disponibilite')) {
                $chambre->disponibilite = $validatedData['disponibilite'];
            }
            if ($request->has('description')) {
                $chambre->description = $validatedData['description'];
            }


        // Save changes to database
            $saved = $chambre->save();
            if (!$saved) {
                return response()->json(['message' => 'Erreur lors de la mise à jour de la chambre'], 500);
            }

            return response()->json([
                'message' => 'Chambre mise à jour avec succès',
                'chambre' => $chambre->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la chambre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    

    public function destroy($id)
    {
        try {
            if (!Auth::user()->is_admin) {
                return response()->json(['message' => 'Action non autorisée'], 403);
            }

            $chambre = Chambre::findOrFail($id);

            if ($chambre->photo) {
                Storage::disk('public')->delete($chambre->photo);
            }

            $chambre->delete();

            return response()->json([
                'message' => 'Chambre supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression de la chambre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function show($id)
{
    try {
        $chambre = Chambre::findOrFail($id);

        return response()->json([
            'success' => true,
            'chambre' => $chambre
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Chambre non trouvée'
        ], 404);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la récupération de la chambre',
            'error' => $e->getMessage()
        ], 500);
    }
}


 public function searchChambres(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $chambres = Chambre::where('disponibilite', 1)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();

        return response()->json($chambres);
    }}