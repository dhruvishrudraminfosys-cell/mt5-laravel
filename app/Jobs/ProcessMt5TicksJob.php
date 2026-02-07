<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Mt5Tick;

class ProcessMt5TicksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
     public function handle()
    {
        // 📊 છેલ્લા 1 minute નો data
        $oneMinuteAgo = now()->subMinute();

        $ticks = Mt5Tick::where('tick_time', '>=', $oneMinuteAgo)
            ->orderBy('tick_time', 'desc')
            ->get();

        if ($ticks->isEmpty()) {
            Log::info('MT5 JOB: No ticks in last minute');
            return;
        }

        // 📈 Analysis કરો
        $analysis = [
            'total_ticks' => $ticks->count(),
            'symbols' => $ticks->pluck('symbol')->unique()->values(),
            'avg_spread' => round($ticks->avg('spread'), 5),
            'max_bid' => $ticks->max('bid'),
            'min_bid' => $ticks->min('bid'),
            'time_range' => [
                'from' => $ticks->last()->tick_time,
                'to' => $ticks->first()->tick_time,
            ],
        ];

        Log::info('MT5 JOB ANALYSIS (1 MIN)', $analysis);

        // 🔔 તમે અહીં notification, alerts વગેરે add કરી શકો
    }
}
