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

class ReservationController extends Controller
{
    public function __construct()
    {
        FedaPay::setEnvironment(env('FEDAPAY_ENVIRONMENT', 'sandbox'));
        FedaPay::setApiKey(env('FEDAPAY_SECRET_KEY'));
    }

    /**
     * Display a listing of the reservations.
     */
    public function index()
    {
        // Récupère toutes les réservations
       // $reservations = Reservation::all();
       $reservations = Reservation::paginate(5);
       return response()->json($reservations);
    }

    /**
     * Store a newly created reservation in storage.
     */
    public function store(StoreReservationRequest $request)
    {
        // Validation des données de la réservation
        $validated = $request->validated();

        try {
            // Calcul de la durée de réservation
            $dateArrive = Carbon::parse($validated['date_arrive']);
            $dateDepart = Carbon::parse($validated['date_depart']);
            $dureeReservation = $dateArrive->diffInDays($dateDepart);

            // Création de la réservation
            $reservation = Reservation::create([
                'user_id' => $validated['user_id'],
                'chambre_id' => $validated['chambre_id'],
                'numero_reservation' => rand(1000, 9999),
                'date_reservation' => now(),
                'date_arrive' => $validated['date_arrive'],
                'date_depart' => $validated['date_depart'],
                'dure_reservation' => $dureeReservation,
            ]);

            // Récupère la chambre et l'utilisateur associés à la réservation
            $chambre = $reservation->chambre;
            $client = User::find($reservation->user_id);

            // Calcul du montant total de la réservation
            $montantTotal = $chambre->prix_nuite * $dureeReservation;

            // Découper le nom du client s'il contient un espace
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
                        'number' => $request->phone_number, // Le client doit fournir son numéro
                        'country' => 'TG'                    // Code pays Togo
                    ]
                ]
            ]);

            // Rediriger vers l'URL de paiement générée par FedaPay
            $token = $transaction->generateToken();

            return response()->json([
                'success' => true,
                'redirect_url' => $token->url
            ]);


        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la création de la réservation ou de la transaction : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified reservation.
     */
    public function show($id)
    {
        // Récupère une réservation spécifique
        $reservation = Reservation::findOrFail($id);
        return response()->json($reservation);
    }

    /**
     * Update the specified reservation in storage.
     */
    public function update(UpdateReservationRequest $request, $id)
    {
        // Validation des données de la réservation
        $validated = $request->validated();

        // Récupère la réservation existante
        $reservation = Reservation::findOrFail($id);

        // Met à jour les informations de la réservation
        $reservation->update([
            'date_arrive' => $validated['date_arrive'],
            'date_depart' => $validated['date_depart'],
        ]);

        return response()->json($reservation);
    }

    /**
     * Remove the specified reservation from storage.
     */
    public function destroy($id)
    {
        // Supprime une réservation
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();

        return response()->json(['message' => 'Reservation deleted successfully']);
    }

    /**
     * Search reservations by user or date.
     */
    public function search(Request $request)
    {
        $query = Reservation::query();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_arrive')) {
            $query->whereDate('date_arrive', $request->date_arrive);
        }

        $reservations = $query->get();

        return response()->json($reservations);
    }
}
