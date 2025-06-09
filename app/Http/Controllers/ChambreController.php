<?php

namespace App\Http\Controllers;

use App\Models\Chambre;
use Illuminate\Http\Request;

class ChambreController extends Controller
{
    /**
     * Afficher la liste des chambres.
     */
    public function index()
    {
        return response()->json(Chambre::all(), 200);
    }

    /**
     * Rechercher les chambres disponibles à une date donnée.
     */
    public function rechercherParDate(Request $request)
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json(['message' => 'Veuillez fournir une date'], 400);
        }

        $chambresDisponibles = Chambre::where('disponibilite', true)
            ->where('date', '>=', $date)
            ->get();

        return response()->json($chambresDisponibles, 200);
    }

    /**
     * Ajouter une nouvelle chambre.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'numero_chambre' => 'required|integer|unique:chambres',
            'type' => 'required|in:Simple,Double,Suite',
            'prix_nuite' => 'required|numeric|min:0',
            'disponibilite' => 'required|boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
            $validatedData['photo'] = $photoPath;
        }

        $chambre = Chambre::create($validatedData);

        return response()->json(['message' => 'Chambre créée avec succès', 'chambre' => $chambre], 201);
    }

    /**
     * Afficher les détails d'une chambre spécifique.
     */
    public function show($id)
    {
        $chambre = Chambre::find($id);

        if (!$chambre) {
            return response()->json(['message' => 'Chambre non trouvée'], 404);
        }

        return response()->json($chambre, 200);
    }

    /**
     * Mettre à jour une chambre.
     */
    public function update(Request $request, $id)
    {
        $chambre = Chambre::find($id);

        if (!$chambre) {
            return response()->json(['message' => 'Chambre non trouvée'], 404);
        }

        $validatedData = $request->validate([
            'numero_chambre' => 'integer|unique:chambres,numero_chambre,' . $id,
            'type' => 'in:Simple,Double,Suite',
            'prix_nuite' => 'numeric|min:0',
            'disponibilite' => 'boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

        ]);

        $chambre->update($validatedData);

        return response()->json(['message' => 'Chambre mise à jour avec succès', 'chambre' => $chambre], 200);
    }

    /**
     * Supprimer une chambre.
     */
    public function destroy($id)
    {
        $chambre = Chambre::find($id);

        if (!$chambre) {
            return response()->json(['message' => 'Chambre non trouvée'], 404);
        }

        $chambre->delete();

        return response()->json(['message' => 'Chambre supprimée avec succès'], 200);
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
    }
}
