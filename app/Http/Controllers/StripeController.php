<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
    public function createDeposit(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1'
        ]);

        try {

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'Wallet Deposit',
                            ],
                            'unit_amount' => (int) ($request->amount * 100),
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => url('/success'),
                'cancel_url' => url('/cancel'),
                'metadata' => [
                    'user_id' => $request->user_id,
                    'amount' => $request->amount
                ]
            ]);

            return response()->json([
                'status' => true,
                'checkout_url' => $session->url
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe Create Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Unable to create payment session'
            ], 500);
        }
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1'
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->balance < $request->amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance'
            ], 400);
        }

        DB::transaction(function () use ($user, $request) {

            $user->balance -= $request->amount;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdraw',
                'amount' => $request->amount,
                'status' => 'success'
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Withdraw successful'
        ]);
    }


    public function webhook(Request $request)
    {
        Log::info('Webhook Hit');

        // Raw payload log karo
        Log::info('Webhook Payload: ' . $request->getContent());

        $event = json_decode($request->getContent());

        if (!$event || !isset($event->type)) {
            Log::error('Invalid Payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info('Event Type: ' . $event->type);

        if ($event->type === 'checkout.session.completed') {

            if (!isset($event->data->object)) {
                Log::error('Session object missing');
                return response()->json(['error' => 'Invalid session data'], 400);
            }

            $session = $event->data->object;

            Log::info('Payment Status: ' . ($session->payment_status ?? 'not set'));

            if (!isset($session->payment_status) || $session->payment_status !== 'paid') {
                Log::warning('Payment not paid');
                return response()->json(['status' => 'ignored']);
            }

            if (!isset($session->metadata->user_id)) {
                Log::error('User ID missing in metadata');
                return response()->json(['error' => 'User ID missing'], 400);
            }

            $userId = $session->metadata->user_id;
            $amount = $session->amount_total / 100;
            $stripeId = $session->payment_intent ?? null;

            Log::info("User ID: $userId | Amount: $amount | Stripe ID: $stripeId");

            if (!$stripeId) {
                Log::error('Stripe ID missing');
                return response()->json(['error' => 'Stripe ID missing'], 400);
            }

            $alreadyExists = Transaction::where('stripe_id', $stripeId)->exists();

            if ($alreadyExists) {
                Log::warning('Duplicate transaction detected');
                return response()->json(['status' => 'already_processed']);
            }

            DB::transaction(function () use ($userId, $amount, $stripeId) {

                $user = User::find($userId);

                if (!$user) {
                    Log::error('User not found: ' . $userId);
                    return;
                }

                $user->balance += $amount;
                $user->save();

                Transaction::create([
                    'user_id' => $userId,
                    'type' => 'deposit',
                    'amount' => $amount,
                    'status' => 'success',
                    'stripe_id' => $stripeId
                ]);

                Log::info('Balance updated successfully');
            });
        }

        return response()->json(['status' => 'success']);
    }
}