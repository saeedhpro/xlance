<?php


namespace App\Repositories;


use App\Events\NewMessageEvent;
use App\Events\NewMessageNotificationEvent;
use App\Http\Requests\AcceptOrRejectChangePrice;
use App\Http\Requests\AcceptOrRejectProjectRequest;
use App\Http\Requests\AddProjectAttachmentRequest;
use App\Http\Requests\ChangeProjectRequest;
use App\Http\Requests\RateFreelancerRequest;
use App\Http\Requests\StoreSecurePaymentRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\ProjectCollectionResource;
use App\Interfaces\ProjectInterface;
use App\Models\CancelProjectRequest;
use App\Models\CancelProjectRequest as CancelProject;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Request;
use App\Models\ChangeProjectRequest as ChangePrice;
use App\Models\SecurePayment;
use App\Models\Transaction;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use MannikJ\Laravel\Wallet\Models\Wallet;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class ProjectRepository extends BaseRepository implements ProjectInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    /**
     * Find one by ID
     * @param int $id
     * @return mixed
     */
    public function find($id)
    {
        return $this->model->where('status', '!=', Project::IN_PAY_STATUS)
            ->where('id', '=', $id)->first();
    }

    /**
     * Find one by ID or throw exception
     * @param int $id
     * @return mixed
     */
    public function findOneOrFail($id)
    {
        return $this->model->where('status', '!=', Project::IN_PAY_STATUS)
            ->where('id', '=', $id)->firstOrFail();
    }

    public function all($columns = array('*'), $orderBy = 'id', $sortBy = 'desc')
    {
//        return $this->model->orderBy($orderBy, $sortBy)->where('status', '=', Project::PUBLISHED_STATUS)->get($columns);
        return $this->model->orderBy($orderBy, $sortBy)->where('status', '=', Project::PUBLISHED_STATUS)->get($columns);
    }

    public function allByPagination($columns = array('*'), $orderBy = 'id', $sortBy = 'desc', $page = 1, $limit = 10)
    {
        return $this->model->orderBy($orderBy, $sortBy)->where('status', '=', Project::PUBLISHED_STATUS)->paginate($page)->get($columns);
    }

    public function created()
    {
        $user = auth()->user();
        return $this->model->where('employer_id', '=', $user->id)->where('status', '=', Project::CREATED_STATUS);
    }

    public function started()
    {
        $user = auth()->user();
        return $this->model->where('employer_id', '=', $user->id)->where('status', '=', Project::STARTED_STATUS)->get();
    }

    public function accepted()
    {
        $user = auth()->user();
        return $this->model->where('employer_id', '=', $user->id)->where('selected_request_id', '!=', null)->get();
    }

    public function finished()
    {
        $user = auth()->user();
        $created = $this->model->where('employer_id', '=', $user->id)->where('status', '=', Project::FINISHED_STATUS)
            ->orWhere('status', '=', Project::CANCELED_STATUS)->get();
        return new ProjectCollectionResource($created);
    }

    public function lasts()
    {
        return $this->model->orderBy('created_at', 'desc')->where('status', '=', Project::PUBLISHED_STATUS)->take(5)->get();
    }

    public function projectPayments($id)
    {
        /** @var Project $project */
        $project = $this->findOneOrFail($id);
        /** @var Request $request */
        $request = Request::find($project->selected_request_id);
        if($request) {
            $requests = SecurePayment::all()->where('request_id', '=', $request->id);
            $requests = $requests->filter(function(SecurePayment $payment) {
                return $payment->status == SecurePayment::ACCEPTED_STATUS ||
                    $payment->status == SecurePayment::PAYED_STATUS ||
                    $payment->status == SecurePayment::FREE_STATUS;
            });
            return $requests;
        } else {
            return [];
        }
    }

    public function projectCreatedPayments($id)
    {
        /** @var Project $project */
        $project = $this->findOneOrFail($id);
        /** @var Request $request */
        $request = Request::findOrFail($project->selected_request_id);
        if($request) {
            $payments = SecurePayment::where('request_id', '=', $request->id)
                ->where('status', '=', SecurePayment::CREATED_STATUS)->get();
            return $payments;
        }
        return [];
    }

    public function addProjectPayments(StoreSecurePaymentRequest $request, $id)
    {
        /** @var Project $project */
        $project = $this->findOneOrFail($id);
        $payments = $request->get('new_secure_payments');
        /** @var Request $req */
        $req = $project->selectedRequest;
        /** @var User $admin */
        $admin = User::all()->filter(function(User $user) {
            return $user->hasRole('admin');
        })->first();
        /** @var Conversation $conversation */
        $conversation = $project->mainConversation;
        foreach ($payments as $p) {
            $req->securePayments()->create([
                'title' => $p['title'],
                'price' => $p['price'],
                'status' => SecurePayment::CREATED_STATUS,
                'user_id' => $project->freelancer->id,
                'to_id' => $project->employer->id,
                'project_id' => $project->id,
            ]);
            $body = 'توضیحات پرداخت امن: "'.$p['title'].'" : "'. $p['price'] .'" تومان ';
            $message = Message::create([
                'user_id' => $admin->id,
                'type' => Message::TEXT_TYPE,
                'conversation_id' => $conversation->id,
                'body' => $body,
                'is_system' => true
            ]);
            $conversation->new_messages_count = $conversation->new_messages_count + 1;
            $conversation->save();
            $employer = $project->employer;
            $freelancer = $project->freelancer;
            $nonce = $this->getNonce();
            broadcast(new NewMessageNotificationEvent($employer, $employer->newMessagesCount(), $nonce));
            $nonce = $this->getNonce();
            broadcast(new NewMessageNotificationEvent($freelancer, $freelancer->newMessagesCount(), $nonce));
            broadcast(new NewMessageEvent($message, $project->employer));
            broadcast(new NewMessageEvent($message, $project->freelancer));
        }
        return $req;
    }

    public function addAttachment(AddProjectAttachmentRequest $request, $id)
    {
        /** @var Project $project */
        $project = $this->findOneOrFail($id);
        $upload = Upload::findOrFail($request->get('file_id'));
        $attachment = $project->attachments()->create([
            'name' => $upload->name,
            'path' => $upload->path,
            'user_id' => auth()->user()->id
        ]);
        return $attachment;
    }

    public function destroyAttachment($id, $attachment_id)
    {
        /** @var Project $project */
        $project = $this->findOneOrFail($id);
        $attachment = $project->attachments()->find($attachment_id);
        try {
            $attachment->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function changePrice(ChangeProjectRequest $request, $id)
    {
        /** @var Project $project */
        $project = $this->findOneOrFail($id);
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->sentChangePriceRequests()->create([
            'type' => $auth->hasRole('employer') ? ChangePrice::EMPLOYER_TYPE : ChangePrice::FREELANCER_TYPE,
            'status' => ChangePrice::CREATED_STATUS,
            'new_price' => $request->get('new_price'),
            'sender_id' => $auth->hasRole('employer') ? $project->employer->id : $project->freelancer->id,
            'receiver_id' => $auth->hasRole('employer') ? $project->freelancer->id : $project->employer->id,
            'project_id' => $project->id
        ]);
    }

    public function acceptOrRejectProjectPayment(AcceptOrRejectChangePrice $changePriceRequest, SecurePayment $payment)
    {
        $accepted =  $changePriceRequest->accepted;
        /** @var Request $request */
        $request = $payment->request;
        /** @var User $user */
        $user = $request->user;
        /** @var Project $project */
        $project = $request->project;
        if($accepted) {
            $payment->update([
                'status' => SecurePayment::ACCEPTED_STATUS
            ]);
            if(!$payment->is_first) {
                $request->update([
                    'price' => $request->price + $payment->price
                ]);
            }
            $notificationBody = 'پرداخت امن به مبلغ '. $payment->price .' تومان تایید شد';
            $project->notifications()->create(array(
                'text' => $notificationBody,
                'type' => Notification::PROJECT,
                'user_id' => $user->id,
                'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
            ));
            Notification::sendNotificationToAll([$user->email], $notificationBody, $notificationBody, null);
            Notification::sendNotificationToUsers(collect([$user]));
        } else {
            $payment->update([
                'status' => SecurePayment::REJECTED_STATUS
            ]);
            $notificationBody = 'پرداخت امن به مبلغ '. $payment->price .' تومان رد شد';
            $project->notifications()->create(array(
                'text' => $notificationBody,
                'type' => Notification::PROJECT,
                'user_id' => $user->id,
                'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
            ));
            Notification::sendNotificationToAll([$user->email], $notificationBody, $notificationBody, null);
            Notification::sendNotificationToUsers(collect([$user]));
        }
        return $accepted;
    }

    public function cancelProjectPayment(SecurePayment $payment)
    {
        if($payment->status == SecurePayment::ACCEPTED_STATUS) {
            /** @var Request $request */
            $request = $payment->request;
            $request->update([
                'price' => $request->price - $payment->price
            ]);
        }
        return $payment->update([
            'status' => SecurePayment::CANCELED_STATUS
        ]);
    }

    public function freeProjectPayment(SecurePayment $payment)
    {
        try {
            $payment->update([
                'status' => SecurePayment::FREE_STATUS
            ]);
            /** @var User $freelancer */
            $freelancer = $payment->user;
            /** @var Wallet $wallet */
            $wallet = $freelancer->wallet;
            $wallet->deposit(((int) $payment->price * 90/100));
            /** @var Request $request */
            $request = $payment->request;
            $request->update([
                'price' => $request->price - $payment->price
            ]);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function payProjectPayment(SecurePayment $payment)
    {
        /** @var User $user */
        $user = auth()->user();
        $balance = $user->wallet->balance;
        $price = $payment->price;
        $project = $payment->project;
        $needPay = $price > $balance;
        if($needPay) {
            $amount = $price - $balance;
            $invoice = new Invoice;
            $invoice->amount((int) ($amount / 10));
            $invoice->detail('t_id', $invoice->getTransactionId());
            return Payment::purchase($invoice, function($driver, $transactionId) use($user, $invoice, $project, $payment){
                Transaction::create([
                    'user_id' =>  $user->id,
                    'transaction_id' => $transactionId,
                    'project_id' => $project->id,
                    'secure_payment_id' => $payment->id,
                    'type' => Transaction::SECURE_PAYMENT_TYPE,
                    'status' => Transaction::CREATED_STATUS,
                    'amount' => $invoice->getAmount(),
                ]);
            })->pay()->toJson();
        } else {
            /** @var Wallet $wallet */
            $wallet = $user->wallet;
            $wallet->setBalance((int) ($balance - $price));
            $payment->update([
                'status' => SecurePayment::PAYED_STATUS
            ]);
            return true;
        }
    }

    public function changePriceRequests($id)
    {
        /** @var Project $project */
        $project = $this->findOneOrFail($id);
        return $project->createdPriceRequests()->get();
    }

    public function finishProject(Project $project)
    {
        $project->update([
            'status' => Project::FINISHED_STATUS,
        ]);
        $project->save();
        $notificationBody = 'کارفرما پروژه ی '. $project->title .' را تمام کرد';
        $project->notifications()->create(array(
            'text' => $notificationBody,
            'type' => Notification::PROJECT,
            'user_id' => $project->employer->id,
            'image_id' => $project->employer->profile->avatar ? $project->employer->profile->avatar->id : null
        ));
        $notificationBody = 'کارفرما پروژه ی '. $project->title .' را تمام کرد';
        $project->notifications()->create(array(
            'text' => $notificationBody,
            'type' => Notification::PROJECT,
            'user_id' => $project->freelancer->id,
            'image_id' => $project->freelancer->profile->avatar ? $project->freelancer->profile->avatar->id : null
        ));
        Notification::sendNotificationToUsers(collect([$project->freelancer]));
        Notification::sendNotificationToUsers(collect([$project->employer]));
        $admins = User::all()->filter(function (User $u){
            return $u->hasRole('admin');
        })->pluck('id');
        $ids2 = collect($admins->values());
        $emails2 = User::all()->whereIn('id', $ids2->toArray())->pluck('email');
        $users = User::all()->whereIn('id', $ids2->toArray());
        Notification::sendNotificationToAll($emails2->toArray(), $notificationBody, $notificationBody, null);
        Notification::sendNotificationToAll([$project->freelancer->email, $project->employer->email], $notificationBody, $notificationBody, null);
        Notification::sendNotificationToUsers($users);
        return true;
    }

    public function rateFreelancer(RateFreelancerRequest $request, Project $project)
    {
        /** @var User $user */
        $user = $project->freelancer;
        /** @var User $emp */
        $emp = $project->employer;
        if(!$user->rates()->where('rater_id', '=', $emp->id)->where('project_id', '=', $project->id)->first()){
            $user->rates()->create([
                'rate' => $request->rate,
                'description' => $request->description,
                'user_id' => $user->id,
                'rater_id' => $emp->id,
                'project_id' => $project->id,
            ]);
            return true;
        }
        return false;
    }

    public function rateEmployer(RateFreelancerRequest $request, Project $project)
    {
        /** @var User $user */
        $user = $project->freelancer;
        /** @var User $emp */
        $emp = $project->employer;
        if(!$user->rates()->where('rater_id', '=', $user->id)->where('project_id', '=', $project->id)->first()){
            $user->rates()->create([
                'rate' => $request->rate,
                'description' => $request->description,
                'user_id' => $emp->id,
                'rater_id' => $user->id,
                'project_id' => $project->id,
            ]);
            return true;
        }
        return false;
    }

    public function sendCancelProjectRequest(Project $project)
    {
        $freelancer = $project->freelancer;
        $employer = $project->employer;
        return CancelProject::create([
            'freelancer_id' => $freelancer->id,
            'employer_id' => $employer->id,
            'project_id' => $project->id,
            'status' => CancelProject::CREATED_STATUS
        ]);
    }

    public function acceptCancelProjectRequest(AcceptOrRejectProjectRequest $request, Project $project)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $accepted = $request->accepted;
        if($accepted) {
            /** @var CancelProjectRequest $r */
            $r = $project->cancelRequests()->where('status', '=', CancelProjectRequest::CREATED_STATUS)->first();
            $r->update([
                'status' => CancelProjectRequest::ACCEPTED_STATUS
            ]);
            $project->update([
               'status' => Project::CANCELED_STATUS,
            ]);
            $this->cancelProjectPayments($auth, $project->payments()->where('status', '!=', SecurePayment::FREE_STATUS)->get());
            $freelancer = $r->freelancer;
            $this->sendNotification($project, $freelancer, true);
            return true;
        } else {
            /** @var CancelProjectRequest $r */
            $r = $project->cancelRequests()->where('status', '=', CancelProjectRequest::CREATED_STATUS)->first();
            $freelancer = $r->freelancer;
            $r->forceDelete();
            $this->sendNotification($project, $freelancer, false);
            return false;
        }
    }

    private function cancelProjectPayments(User $user, Collection $payments)
    {
        $price = 0;
        /** @var Wallet $wallet */
        $wallet = $user->wallet;
        /** @var SecurePayment $p */
        foreach ($payments as $p) {
            if($p->status == SecurePayment::PAYED_STATUS) {
                $price = $price + $p->price;
            }
            $p->update([
               'status' => SecurePayment::CANCELED_STATUS
            ]);
        }
        $wallet->deposit((int) $price);
    }

    private function sendNotification(Project $project, User $user, bool $accepted)
    {
        $notification = $project->notifications()->create(array(
            'text' => $accepted ? 'درخواست لغو پروژه ی '. $project->title .' تایید شد' : 'درخواست لغو پروژه ی '. $project->title .' تایید نشد',
            'type' => Notification::PROJECT,
            'user_id' => $user->id,
            'image_id' => $project->freelancer->profile->avatar ? $project->freelancer->profile->avatar->id : null
        ));
        $users = collect();
        $users->add($user);
        Notification::sendNotificationToAll([$user->email], $accepted ? 'درخواست لغو پروژه ی '. $project->title .' تایید شد' : 'درخواست لغو پروژه ی '. $project->title .' تایید نشد',$accepted ? 'درخواست لغو پروژه ی '. $project->title .' تایید شد' : 'درخواست لغو پروژه ی '. $project->title .' تایید نشد', null);
        Notification::sendNotificationToUsers($users);
    }
}
