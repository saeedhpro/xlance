<?php

namespace App\Events;

use App\Http\Resources\ConversationResource;
use App\Http\Resources\SimpleUserResource;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewConversationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $conversation;
    private $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param Conversation $conversation
     */
    public function __construct(User $user, Conversation $conversation)
    {
        $this->conversation = $conversation;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('new.conversations.'.$this->user->id);
    }


    public function broadcastWith()
    {
        return ['conversation' => new ConversationResource($this->conversation)];
    }
}
