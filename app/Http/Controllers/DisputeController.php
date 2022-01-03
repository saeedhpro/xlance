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
                $project->notifications()->create([
                    'text' => $freelancer->first_name . '' . $freelancer->last_name . 'برای پروژه ' . $project->title . ' اختلاف ایجاد کرده است.',
                    'type' => Notification::ِDISPUTE,
                    'user_id' => $employer->id,
                    'notifiable_id' => $project->id,
                    'image_id' => null
                ]);
                Notification::sendNotificationToUsers(collect([$freelancer]));
                $admins = User::query()->with('roles')->whereHas('roles', function ($q) {
                    $q->where('name', '=', 'admin');
                })->get();
                $freelancer = $project->freelancer;
                foreach ($admins as $admin) {
                    $project->notifications()->create([
                        'text' => $freelancer->first_name . '' . $freelancer->last_name . 'برای پروژه ' . $project->title . ' اختلاف ایجاد کرده است.',
                        'type' => Notification::ADMIN_PROJECT,
                        'user_id' => $admin->id,
                        'image_id' => null
                    ]);
                    Notification::sendNotificationToUsers(collect([$admin]));
                }
            } else {
                $message = Message::create([
                    'user_id' => $auth->id,
                    'type' => Message::TEXT_TYPE,
                    'conversation_id' => $ec->id,
                    'body' => $body,
                    'is_system' => true
                ]);
                $nonce = $this->getNonce();
                broadcast(new NewMessageNotificationEvent($employer, $employer->newMessagesCount(), $nonce));
                $project->notifications()->create([
                    'text' => $employer->first_name . '' . $employer->last_name . 'برای پروژه ' . $project->title . ' اختلاف ایجاد کرده است.',
                    'type' => Notification::ِDISPUTE,
                    'user_id' => $freelancer->id,
                    'notifiable_id' => $project->id,
                    'image_id' => null
                ]);
                Notification::sendNotificationToUsers(collect([$employer]));
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
            $notificationBody = 'ادمین حل اختلاف پروژه ی '. $project->title .' را تمام کرد';
            $project->notifications()->create(array(
                'text' => $notificationBody,
                'type' => Notification::PROJECT,
                'user_id' => $project->freelancer->id,
                'image_id' => $project->freelancer->profile->avatar ? $project->freelancer->profile->avatar->id : null
            ));
            $notificationBody = 'ادمین حل اختلاف پروژه ی '. $project->title .' را تمام کرد';
            $project->notifications()->create(array(
                'text' => $notificationBody,
                'type' => Notification::PROJECT,
                'user_id' => $project->employer->id,
                'image_id' => $project->employer->profile->avatar ? $project->employer->profile->avatar->id : null
            ));
            Notification::sendNotificationToAll([$project->freelancer->email], $notificationBody, $notificationBody, null);
            Notification::sendNotificationToAll([$project->employer->email], $notificationBody, $notificationBody, null);
            Notification::sendNotificationToUsers(collect([$project->freelancer]));
            Notification::sendNotificationToUsers(collect([$project->employer]));
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
