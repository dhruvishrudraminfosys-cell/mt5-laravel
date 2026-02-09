<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TickUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticks;

    public function __construct($ticks)
    {
        // Accept either single tick or array of ticks
        $this->ticks = is_array($ticks) && isset($ticks[0]) ? $ticks : [$ticks];

        // Log construction
        Log::debug('TickUpdated Event Created', [
            'tick_count' => count($this->ticks),
            'symbols' => array_column($this->ticks, 'symbol'),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB'
        ]);
    }

    public function broadcastOn()
    {
        Log::debug('Broadcasting on channel: market-ticks');
        return new Channel('market-ticks');
    }

    public function broadcastAs()
    {
        return 'tick.updated';
    }

    public function broadcastWith()
    {
        $timestamp = now()->format('H:i:s.u');
        $payload = [
            'ticks' => $this->ticks,
            'timestamp' => $timestamp,
            'count' => count($this->ticks)
        ];

        // Detailed logging
        Log::debug('📡 Broadcasting Tick Data', [
            'event' => 'tick.updated',
            'channel' => 'market-ticks',
            'timestamp' => $timestamp,
            'tick_count' => count($this->ticks),
            'symbols' => array_column($this->ticks, 'symbol'),
            'payload_size' => strlen(json_encode($payload)) . ' bytes',
            'ticks' => $this->ticks // Full tick data
        ]);

        return $payload;
    }
}
