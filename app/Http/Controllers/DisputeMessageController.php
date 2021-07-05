<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDisputeMessageRequest;
use App\Http\Resources\DisputeMessageResource;
use App\Interfaces\DisputeMessageInterface;
use App\Models\Dispute;
use App\Models\User;

class DisputeMessageController extends Controller
{
    private $disputeMessageRepository;
    public function __construct(DisputeMessageInterface $disputeMessageRepository)
    {
        $this->disputeMessageRepository = $disputeMessageRepository;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreDisputeMessageRequest $request
     * @param $id
     * @return DisputeMessageResource
     */
    public function store(StoreDisputeMessageRequest $request, $id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Dispute $dispute */
        $dispute = Dispute::findOrFail($id);
        $request['dispute_id'] = $dispute->id;
        $request['sender_id'] = $auth->id;
        $message = $this->disputeMessageRepository->create($request->only([
            'dispute_id',
            'sender_id',
            'body',
        ]));
        return new DisputeMessageResource($message);
    }
}
