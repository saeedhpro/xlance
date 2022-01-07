<?php

namespace App\Http\Controllers;

use App\Events\NewConversationEvent;
use App\Events\NewMessageNotificationEvent;
use App\Http\Requests\CloseDisputeRequest;
use App\Http\Requests\StoreDisputeRequest;
use App\Http\Resources\DisputeMessageCollectionResource;
use App\Http\Resources\DisputeResource;
use App\Http\Resources\NotificationResource;
use App\Interfaces\DisputeInterface;
use App\Models\Conversation;
use App\Models\Dispute;
use App\Models\DisputeChat;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Project;
use App\Models\SecurePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DisputeController extends Controller
{
    private $disputeRepository;
    public function __construct(DisputeInterface $disputeRepository)
    {
        $this->disputeRepository = $disputeRepository;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreDisputeRequest $request
     * @return DisputeResource
     */
    public function store(StoreDisputeRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Project $project */
        $project = Project::findOrFail($request->project_id);
        if($auth->can('create-dispute', $project)) {
            $request['freelancer_id'] = $project->freelancer->id;
            $request['employer_id'] = $project->employer->id;
            $dispute = $this->disputeRepository->create($request->only([
                'title',
                'freelancer_id',
                'employer_id',
                'project_id',
            ]));
            $project->update([
                'status' => Project::DISPUTED_STATUS,
            ]);
            /** @var User $admin */
            $admin = User::all()->filter(function (User $user) {
                return $user->hasRole('admin');
            })->first();
            /** @var User $freelancer */
            $freelancer = $project->freelancer;
            /** @var User $employer */
            $employer = $project->employer;
            /** @var Conversation $fc */
            $fc = $freelancer->conversations()->create([
                'user_id' => $admin->id,
                'to_id' => $freelancer->id,
                'type' => Conversation::DISPUTE_TYPE
            ]);
            $body = 'یک اختلاف توسط "'.$auth->username.'" برای پروژه ی "' . $project->title . '" ایجاد شد';
            $message = Message::create([
                'user_id' => $admin->id,
                'type' => Message::TEXT_TYPE,
                'conversation_id' => $fc->id,
                'body' => $body,
                'is_system' => true
            ]);
            $nonce = $this->getNonce();
            broadcast(new NewConversationEvent($freelancer, $fc));
            broadcast(new NewMessageNotificationEvent($freelancer, $freelancer->newMessagesCount(), $nonce));
            broadcast(new NewConversationEvent($admin, $fc));
            $nonce = $this->getNonce();
            broadcast(new NewMessageNotificationEvent($admin, $admin->newMessagesCount(), $nonce));
            /** @var Conversation $ec */
            $ec = $employer->conversations()->create([
                'user_id' => $admin->id,
                'to_id' => $employer->id,
                'type' => Conversation::DISPUTE_TYPE
            ]);
            $message = Message::create([
                'user_id' => $admin->id,
                'type' => Message::TEXT_TYPE,
                'conversation_id' => $ec->id,
                'body' => $body,
                'is_system' => true
            ]);
            broadcast(new NewConversationEvent($employer, $ec));
            broadcast(new NewConversationEvent($admin, $ec));
            $nonce = $this->getNonce();
            broadcast(new NewMessageNotificationEvent($admin, $admin->newMessagesCount(), $nonce));
            $nonce = $this->getNonce();
            broadcast(new NewMessageNotificationEvent($employer, $employer->newMessagesCount(), $nonce));
            $body = 'توضیحات اختلاف: ' . $request->get('title');
            if($auth->id === $freelancer->id) {
                $message = Message::create([
                    'user_id' => $auth->id,
                    'type' => Message::TEXT_TYPE,
                    'conversation_id' => $fc->id,
                    'body' => $body,
                    'is_system' => true
                ]);
                $nonce = $this->getNonce();
                broadcast(new NewMessageNotificationEvent($freelancer, $freelancer->newMessagesCount(), $nonce));
                $text = $freelancer->username . 'برای پروژه ' . $project->title . ' اختلاف ایجاد کرده است.';
                $type = Notification::ِDISPUTE;
                Notification::make(
                    $type,
                    $text,
                    $employer->id,
                    $text,
                    get_class($project),
                    $project->id,
                    false,
                );
                $text = $freelancer->username . 'برای پروژه ' . $project->title . ' اختلاف ایجاد کرده است.';
                $type = Notification::ADMIN_PROJECT;
                Notification::make(
                    $type,
                    $text,
                    null,
                    $text,
                    get_class($project),
                    $project->id,
                    true,
                );
            } else {
                Message::create([
                    'user_id' => $auth->id,
                    'type' => Message::TEXT_TYPE,
                    'conversation_id' => $ec->id,
                    'body' => $body,
                    'is_system' => true
                ]);
                $nonce = $this->getNonce();
                broadcast(new NewMessageNotificationEvent($employer, $employer->newMessagesCount(), $nonce));
                $text = $employer->first_name . '' . $employer->last_name . 'برای پروژه ' . $project->title . ' اختلاف ایجاد کرده است.';
                $type = Notification::ِDISPUTE;
                Notification::make(
                    $type,
                    $text,
                    $freelancer->id,
                    $text,
                    get_class($project),
                    $project->id,
                    false,
                );
            }
            return new DisputeResource($dispute);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return DisputeResource
     */
    public function show($id)
    {
        /** @var Dispute $dispute */
        $dispute = $this->disputeRepository->findOneOrFail($id);
        return new DisputeResource($dispute);
    }

    public function messages($id)
    {
        /** @var Dispute $dispute */
        $dispute = $this->disputeRepository->findOneOrFail($id);
        /** @var Project $project */
        $project = $dispute->project()->get();
        /** @var User $auth */
        $auth = auth()->user();
        $sender = null;
        if($auth->id === $project->freelancer->id) {
            $sender = $project->employer()->get();
            $messages = $dispute->messages()->where('sender_id', '!=', $sender->id);
        } elseif($auth->id === $project->employer->id) {
            $sender = $project->freelancer()->get();
            $messages = $dispute->messages()->where('sender_id', '!=', $sender->id);
        } elseif($auth->hasRole('admin')) {
            $messages = $dispute->messages()->get();
        } else {
            return $this->accessDeniedResponse();
        }
        return new DisputeMessageCollectionResource($messages);
    }

    public function close(CloseDisputeRequest $request, $id)
    {
        /** @var Dispute $dispute */
        $dispute = $this->disputeRepository->findOneOrFail($id);
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->hasAnyRole(['admin', 'support'])) {
            $dispute->status = Dispute::CLOSED_STATUS;
            $dispute->save();
            /** @var Project $project */
            $project = $dispute->project;
            $project->update([
                'status' => Project::CANCELED_STATUS,
            ]);
            $project->save();
            $this->cancelSecurePayments($project);
            $text = 'ادمین حل اختلاف پروژه ی '. $project->title .' را تمام کرد';
            $type = Notification::PROJECT;
            Notification::make(
                $type,
                $text,
                $project->freelancer->id,
                $text,
                get_class($project),
                $project->id,
                false,
            );
            $text = 'ادمین حل اختلاف پروژه ی '. $project->title .' را تمام کرد';
            $type = Notification::PROJECT;
            Notification::make(
                $type,
                $text,
                $project->employer->id,
                $text,
                get_class($project),
                $project->id,
                false,
            );
            return new DisputeResource($dispute);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function progress(CloseDisputeRequest $request, $id)
    {
        /** @var Dispute $dispute */
        $dispute = $this->disputeRepository->findOneOrFail($id);
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->hasAnyRole(['admin', 'support'])) {
            $dispute->status = Dispute::IN_PROGRESS_STATUS;
            $dispute->save();
            return new DisputeResource($dispute);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    private function cancelSecurePayments(Project $project)
    {
        $payments = $project->payments()->get()->filter(function (SecurePayment $payment) {
            return $payment->status == SecurePayment::ACCEPTED_STATUS ||
                $payment->status == SecurePayment::CREATED_STATUS ||
                $payment->status == SecurePayment::PAYED_STATUS;
        });
        foreach ($payments as $payment) {
            $payment->update([
                'status' => SecurePayment::CANCELED_STATUS,
            ]);
        }
    }
}
