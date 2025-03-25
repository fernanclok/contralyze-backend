<?php

namespace App\Events;

use App\Models\BudgetRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BudgetRequestStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $budgetRequest;

    public function __construct(BudgetRequest $budgetRequest)
    {
        $this->budgetRequest = $budgetRequest;
    }

    public function broadcastOn()
    {
        return new Channel('budget-requests');
    }

    public function broadcastAs()
    {
        return 'status-updated';
    }
}
