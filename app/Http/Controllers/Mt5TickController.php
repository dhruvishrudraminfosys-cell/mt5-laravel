<?php

namespace App\Http\Controllers;

use App\Models\Mt5Tick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Mt5TickController extends Controller
{
    public function store1(Request $request)
    {
        try {
            // 🔥 Validate incoming data
            $validated = $request->validate([
                'symbol' => 'required|string',
                'bid' => 'required|numeric',
                'ask' => 'required|numeric',
                'time' => 'required|integer',
            ]);

            // 📊 Calculate spread
            $spread = round($validated['ask'] - $validated['bid'], 5);

            $tick = Mt5Tick::create([
                'symbol' => $validated['symbol'],
                'bid' => $validated['bid'],
                'ask' => $validated['ask'],
                'spread' => $spread,
                'tick_time' => date('Y-m-d H:i:s', $validated['time']),
            ]);

            Log::info('MT5 TICK SAVED', [
                'id' => $tick->id,
                'symbol' => $tick->symbol,
                'bid' => $tick->bid,
                'ask' => $tick->ask,
                'spread' => $spread,
                'time' => $tick->tick_time,
            ]);

            $signal = 'NONE';
            if ($spread > 0 && $spread < 0.001) {
                $signal = 'LOW_SPREAD';
            } elseif ($spread >= 0.001) {
                $signal = 'HIGH_SPREAD';
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Tick data saved successfully',
                'data' => [
                    'id' => $tick->id,
                    'symbol' => $tick->symbol,
                    'bid' => $tick->bid,
                    'ask' => $tick->ask,
                    'spread' => $spread,
                    'time' => $tick->tick_time->format('Y-m-d H:i:s'),
                    'signal' => $signal,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('MT5 TICK ERROR', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // 📊 Latest ticks જોવા માટે
    public function index()
    {
        $ticks = Mt5Tick::latest('tick_time')
            ->take(100)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Tick data fetched successfully',
            'count' => $ticks->count(),
            'data' => $ticks,
        ]);
    }

    // Multiple Entry Update Or Create
    public function store(Request $request)
    {
        try {

            // ✅ Validate incoming data
            $validated = $request->validate([
                'symbol' => 'required|string',
                'bid'    => 'required|numeric',
                'ask'    => 'required|numeric',
                'time'   => 'required|integer',
            ]);

            // ✅ Calculate spread
            $spread = round($validated['ask'] - $validated['bid'], 5);

            // ✅ Create or Update based on symbol
            $tick = Mt5Tick::updateOrCreate(
                [
                    'symbol' => $validated['symbol']
                ],
                [
                    'bid'       => $validated['bid'],
                    'ask'       => $validated['ask'],
                    'spread'    => $spread,
                    'tick_time' => date('Y-m-d H:i:s', $validated['time']),
                ]
            );

            // ✅ Logging
            Log::info('MT5 TICK SAVED/UPDATED', [
                'id'     => $tick->id,
                'symbol' => $tick->symbol,
                'bid'    => $tick->bid,
                'ask'    => $tick->ask,
                'spread' => $spread,
                'time'   => $tick->tick_time,
            ]);

            // ✅ Signal Logic
            $signal = 'NONE';

            if ($spread > 0 && $spread < 0.001) {
                $signal = 'LOW_SPREAD';
            } elseif ($spread >= 0.001) {
                $signal = 'HIGH_SPREAD';
            }

            // ✅ Response
            return response()->json([
                'status'  => 'success',
                'message' => 'Tick data saved successfully',
                'data' => [
                    'id'     => $tick->id,
                    'symbol' => $tick->symbol,
                    'bid'    => $tick->bid,
                    'ask'    => $tick->ask,
                    'spread' => $spread,
                    'time'   => \Carbon\Carbon::parse($tick->tick_time)->format('Y-m-d H:i:s'),
                    'signal' => $signal,
                ],
            ], 201);
        } catch (\Exception $e) {

            Log::error('MT5 TICK ERROR', [
                'error'   => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}



