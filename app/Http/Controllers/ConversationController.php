<?php

namespace App\Http\Controllers;

use App\Events\NewConversationEvent;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageCollectionResource;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function show(Conversation $conversation)
    {
        return new ConversationResource($conversation);
    }

    public function store(StoreConversationRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->can('create-conversation', $request->get('to_id'))){
            /** @var Conversation $conversation */
            $conversation = $auth->conversations()->create([
                'user_id' => $auth->id,
                'to_id' => $request->get('to_id'),
                'status' => Conversation::OPEN_STATUS,
                'type' => Conversation::DIRECT_TYPE,
            ]);
            broadcast(new NewConversationEvent($auth, $conversation));
            return new ConversationResource($conversation);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function messages(Conversation $conversation)
    {
        return new MessageCollectionResource($conversation->messages);
    }
}
