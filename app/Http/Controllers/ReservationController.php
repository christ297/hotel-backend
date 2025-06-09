<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Chambre;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use Carbon\Carbon;
use FedaPay\Transaction;
use FedaPay\FedaPay;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        FedaPay::setEnvironment(env('FEDAPAY_ENVIRONMENT', 'sandbox'));
        FedaPay::setApiKey(env('FEDAPAY_SECRET_KEY'));
    }

    /**
     * Display a listing of the reservations.
     */
    public function index()
    {
        // Seuls les admins peuvent voir toutes les réservations
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $reservations = Reservation::with(['user', 'chambre'])->paginate(10);
        return response()->json($reservations);
    }

    /**
     * Store a newly created reservation in storage.
     */
    public function store(StoreReservationRequest $request)
    {
        $validated = $request->validated();

        try {
            // Vérifier que l'utilisateur fait une réservation pour lui-même ou est admin
            if ($validated['user_id'] != Auth::id() && !Auth::user()->is_admin) {
                return response()->json(['message' => 'Vous ne pouvez pas réserver pour un autre utilisateur'], 403);
            }

            $dateArrive = Carbon::parse($validated['date_arrive']);
            $dateDepart = Carbon::parse($validated['date_depart']);
            
            // Vérifier que la chambre est disponible
            $chambre = Chambre::findOrFail($validated['chambre_id']);
            if (!$chambre->disponibilite) {
                return response()->json(['message' => 'Cette chambre n\'est pas disponible'], 400);
            }

            // Vérifier qu'il n'y a pas de conflit de réservation
            $existingReservation = Reservation::where('chambre_id', $validated['chambre_id'])
                ->where(function($query) use ($dateArrive, $dateDepart) {
                    $query->whereBetween('date_arrive', [$dateArrive, $dateDepart])
                          ->orWhereBetween('date_depart', [$dateArrive, $dateDepart]);
                })->exists();

            if ($existingReservation) {
                return response()->json(['message' => 'La chambre est déjà réservée pour cette période'], 400);
            }

            $dureeReservation = $dateArrive->diffInDays($dateDepart);

            $reservation = Reservation::create([
                'user_id' => $validated['user_id'],
                'chambre_id' => $validated['chambre_id'],
                'numero_reservation' => 'RES-' . strtoupper(uniqid()),
                'date_arrive' => $validated['date_arrive'],
                'date_depart' => $validated['date_depart'],
                'dure_reservation' => $dureeReservation,
            ]);

            $client = User::findOrFail($validated['user_id']);
            $montantTotal = $chambre->prix_nuite * $dureeReservation;

            $nameParts = explode(' ', $client->name);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

            // Paiement avec FedaPay
            $transaction = Transaction::create([
                'description' => "Paiement réservation chambre {$chambre->type} (n°{$chambre->numero_chambre}) pour {$dureeReservation} nuit(s)",
                'amount' => $montantTotal,
                'currency' => ['iso' => 'XOF'],
                'customer' => [
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'email' => $client->email,
                    'phone_number' => [
                        'number' => $request->phone_number, 
                        'country' => 'TG'                    
                    ]
                ]
            ]);

            $token = $transaction->generateToken();

            if ($token) {
                $chambre->disponibilite = 0;
                $chambre->save();
                
                return response()->json([
                    'success' => true,
                    'redirect_url' => $token->url,
                    'reservation' => $reservation
                ]);
            }

            return response()->json(['error' => 'Erreur lors de la génération du lien de paiement'], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création de la réservation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified reservation.
     */
    public function show($id)
    {
        $reservation = Reservation::with(['user', 'chambre'])->findOrFail($id);
        
        // Seul l'utilisateur concerné ou un admin peut voir la réservation
        if ($reservation->user_id != Auth::id() && !Auth::user()->is_admin) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        return response()->json($reservation);
    }

    /**
     * Update the specified reservation in storage.
     */
    public function update(UpdateReservationRequest $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        
        // Seul un admin peut modifier une réservation
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validated();

        try {
            $dateArrive = Carbon::parse($validated['date_arrive']);
            $dateDepart = Carbon::parse($validated['date_depart']);
            $dureeReservation = $dateArrive->diffInDays($dateDepart);

            $reservation->update([
                'date_arrive' => $validated['date_arrive'],
                'date_depart' => $validated['date_depart'],
                'dure_reservation' => $dureeReservation,
            ]);

            return response()->json($reservation);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified reservation from storage.
     */
    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);
               

        try {
            $chambre = $reservation->chambre;
            $chambre->disponibilite = 1;
            $chambre->save();
            
            $reservation->delete();

            return response()->json(['message' => 'Réservation annulée avec succès']);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's reservations.
     */
    public function userReservations()
    {
        $reservations = Reservation::with('chambre')
            ->where('user_id', Auth::id())
            ->orderBy('date_arrive', 'desc')
            ->get();

        return response()->json($reservations);
    }

    /**
     * Search reservations by date range (admin only).
     */
    public function search(Request $request)
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $reservations = Reservation::with(['user', 'chambre'])
            ->whereBetween('date_arrive', [$request->start_date, $request->end_date])
            ->orWhereBetween('date_depart', [$request->start_date, $request->end_date])
            ->get();

        return response()->json($reservations);
    }
}