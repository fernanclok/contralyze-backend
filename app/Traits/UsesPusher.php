<?php

namespace App\Traits;

use Pusher\Pusher;

trait UsesPusher
{
    protected function getPusher(): Pusher
    {
        return app('pusher');
    }

    protected function pushEvent(string $channel, string $event, array $data): void
    {
        try {
            $this->getPusher()->trigger($channel, $event, $data);
        } catch (\Exception $e) {
            \Log::error('Error pushing event: ' . $e->getMessage(), [
                'channel' => $channel,
                'event' => $event,
                'data' => $data
            ]);
        }
    }
}
