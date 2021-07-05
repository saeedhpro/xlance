<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $user;
    private $count;
    private $nonce;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, int $count, int $nonce)
    {
        $this->user = $user;
        $this->count = $count;
        $this->nonce = $nonce;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        echo $this->user->id;
        return new Channel('new.notifications.'.$this->user->id);
    }

    public function broadcastWith()
    {
        return ['count' => $this->count, 'nonce' => $this->nonce];
    }
}
