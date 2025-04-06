<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Afficher la liste des utilisateurs.
     * Accessible uniquement par l'administrateur.
     */
    public function index()
    {
       // $this->authorize('viewAny', User::class);  // Assurez-vous que l'utilisateur est autorisé à voir tous les utilisateurs
        return response()->json(User::all(), 200);
    }

    /**
     * Enregistrer un nouvel utilisateur.
     * Accessible à tous les utilisateurs non authentifiés.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user
        ], 201);
    }


    //login 

    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Identifiants invalides'], 401);
    }

    // Si l'utilisateur est authentifié, générez un token
    $token = $user->createToken('API Token')->plainTextToken;

    return response()->json([
        'message' => 'Connexion réussie',
        'token' => $token,
        'is_admin'=>$user->is_admin,
    ], 200);
}

    /**
     * Afficher un utilisateur spécifique.
     * Accessible uniquement par l'utilisateur lui-même ou l'administrateur.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Vérifier si l'utilisateur connecté est celui qui demande les infos ou si c'est un administrateur
      /*  if (Auth::id() != $user->id && !Auth::user()->is_admin) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }*/

        return response()->json($user, 200);
    }

    /**
     * Mettre à jour un utilisateur.
     * Accessible uniquement par l'utilisateur lui-même ou l'administrateur.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Vérifier si l'utilisateur connecté est celui qui tente de modifier ses informations ou s'il est un administrateur
       /* if (Auth::id() != $user->id && !Auth::user()->is_admin) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }*/

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:6',
        ]);

        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);

        $user->save();

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user
        ], 200);
    }

    /**
     * Supprimer un utilisateur.
     * Accessible uniquement par un administrateur.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Assurez-vous que seul un administrateur peut supprimer un utilisateur
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé avec succès'], 200);
    }
}

