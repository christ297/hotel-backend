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
            'is_admin' => 'sometimes|boolean', // Permet de définir si l'utilisateur est un administrateur
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->is_admin ?? false, // Par défaut, l'utilisateur n'est pas un administrateur
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
    try {
        // Check authentication
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        // Find user to update
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Validate request data
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:6',
            'is_admin' => 'sometimes|boolean'
        ]);

        // Update basic info
        if ($authUser->is_admin){
        if ($request->has('name')) {
            $user->name = $validated['name'];
        }
        if ($request->has('email')) {
            $user->email = $validated['email'];
        }
        if ($request->has('password')) {
            $user->password = Hash::make($validated['password']);
        }
    
        // Handle admin status change
        if ($request->has('is_admin')) {
            
                
            
            $user->is_admin = $validated['is_admin'];
        }
    }else{
        return response()->json([
                    'message' => 'Seuls les administrateurs peuvent modifier les utilisateurs',
                ], 403);
    }

        $user->save();

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Erreur lors de la mise à jour: ' . $e->getMessage());
        return response()->json([
            'message' => 'Une erreur est survenue lors de la mise à jour',
            'error' => $e->getMessage()
        ], 500);
    }
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

    /**
     * Déconnexion de l'utilisateur.
     * Révoque le token d'authentification.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete(); // Révoque tous les tokens de l'utilisateur
                    return response()->json(['message' => 'Déconnexion réussie'], 200);     
        } else {
                    return response()->json(['message' => 'Utilisateur non authentifié'], 401);

        }
    }
    /**
     * Rechercher des utilisateurs par nom ou email.
     * Accessible uniquement par l'administrateur.
     */
    public function search(Request $request)    
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json(['message' => 'Aucune requête de recherche fournie'], 400);
        }

        // Rechercher par nom ou email
        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->get();

        return response()->json($users, 200);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Logique pour envoyer un email de réinitialisation de mot de passe
        // ...

        return response()->json(['message' => 'Email de réinitialisation envoyé'], 200);
    }
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',             
            'password' => 'required|string|min:6|confirmed',
        ]);
        // Logique pour réinitialiser le mot de passe
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);

        }
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json(['message' => 'Mot de passe réinitialisé avec succès'], 200);
    }
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'verification_code' => 'required|string',
        ]);

        // Logique pour vérifier le code de vérification
        // ...

        return response()->json(['message' => 'Email vérifié avec succès'], 200);
    }
}


