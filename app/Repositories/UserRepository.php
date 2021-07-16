<?php


namespace App\Repositories;

use App\Events\NewConversationEvent;
use App\Events\NewMessageEvent;
use App\Events\NewMessageNotificationEvent;
use App\Http\Requests\AcceptOrRejectProjectRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SeenConversationRequest;
use App\Http\Requests\SeenMessageRequest;
use App\Http\Requests\SendRequestForProjectRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdateShebaRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\NotificationResource;
use App\Interfaces\UserInterface;
use App\Jobs\ValidateUserJob;
use App\Mail\RegisteredMail;
use App\Models\AcceptFreelancerRequest;
use App\Models\Conversation;
use App\Models\Dispute;
use App\Models\Image;
use App\Models\Message;
use App\Models\Notification;
use App\Models\PaymentHistory;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Request as ProjectRequest;
use App\Models\SecurePayment;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Upload;
use App\Models\User;
use App\Models\WithdrawRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use MannikJ\Laravel\Wallet\Exceptions\UnacceptedTransactionException;
use MannikJ\Laravel\Wallet\Models\Wallet;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;
use Spatie\Permission\Models\Role;

class UserRepository extends BaseRepository implements UserInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function register(RegisterRequest $request): User
    {
        $request['password'] = bcrypt($request->get('password'));
        $request['number'] = 5;
        /** @var User $user */
        $user = $this->create($request->only([
            'username',
            'email',
            'password',
            'number',
        ]));
        $user = $this->setRegisteredUserRole($request, $user);
        $user = $this->setIntroducer($request, $user);
        $user->profile()->create();
        $user->wallet()->create();
        //needs to delete
        $user->save();
        Mail::to($user->email)->send(new RegisteredMail($user));
//        event(new Registered($user));
        return $user;
    }

    public function setRegisteredUserRole(Request $request, User $user)
    {
        $userRole = $request->get('role');
        $employer = Role::findByName('employer');
        $freelancer  = Role::findByName('freelancer');
        $user->as_employer = $userRole == 'employer';
        $user->assignRole($userRole == 'employer' ? $employer : $freelancer);
        $user->assignRole($employer);
        return $user;
    }

    public function login(LoginRequest $request)
    {
        /** @var User $user */
        $user = User::with(['profile', 'rates', 'experiences', 'educations', 'achievements', 'skills'])->whereEmail($request->username)
        ->orWhere('username', $request->username)
        ->first();
        return $user;
    }

    public function freelancers() {
        $freelancers = User::all()->filter(function (User $user) {
            return $user->hasRole('freelancer');
        });
        $freelancers = $freelancers->sort(function (User $first, User $second) {
            if($first->calcRates() == $second->calcRates()) {
                return 0;
            }
            return $first->calcRates() > $second->calcRates() ? -1 : 1;
        });
        return $freelancers;
    }

    public function employers() {
        $employers = User::all()->filter(function (User $user) {
            return $user->hasRole('employer');
        });
        return $employers;
    }

    public function updateProfile(UpdateProfileRequest $request, User $user)
    {
        $languages_list = $request->get('languages_list');
        $languages = implode(',', $languages_list);
        $attributes = array(
            'position' => $request->position,
            'description' => $request->description,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'birth_date' => $request->birth_date,
            'languages' => $languages,
        );
        $user->profile()->update($attributes);
        $notification = $user->notifs()->create(array(
            'text' => 'اطلاعات کاربر ویرایش شد',
            'type' => $user->hasRole('freelancer') ? Notification::FREELANCER : Notification::EMPLOYER,
            'user_id' => $user->id,
            'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
        ));
        $admins = User::all()->filter(function (User $u){
            return $u->hasRole('admin');
        })->pluck('id');
        $ids = collect([]);
        $ids->push($admins->values());
        $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
        $users = User::all()->whereIn('id', $ids->toArray());
        Notification::sendNotificationToAll($emails->toArray(), 'اطلاعات کاربر ویرایش شد', 'اطلاعات کاربر ویرایش شد', null);
        dispatch(new ValidateUserJob($user));
        Notification::sendNotificationToUsers($users);
        return $user;
    }

    public function updateMe(UpdateUserRequest $request, User $user)
    {
        $user->update($request->only([
            'city_id',
            'country_id',
            'state_id',
            'as_employer',
            'phone_number',
            'first_name',
            'last_name',
        ]));
        $notification = $user->notifs()->create(array(
            'text' => 'اطلاعات کاربر ویرایش شد',
            'type' => $user->hasRole('freelancer') ? Notification::FREELANCER : Notification::EMPLOYER,
            'user_id' => $user->id,
            'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
        ));
        $admins = User::all()->filter(function (User $u){
            return $u->hasRole('admin');
        })->pluck('id');
        $ids = collect([]);
        $ids->push($admins->values());
        $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
        $users = User::all()->whereIn('id', $ids->toArray());
        Notification::sendNotificationToAll($emails->toArray(), 'اطلاعات کاربر ویرایش شد', 'اطلاعات کاربر ویرایش شد', null);
        Notification::sendNotificationToUsers($users);
        dispatch(new ValidateUserJob($user));
        return $user;
    }

    public function updateAvatar(UpdateAvatarRequest $request, Profile $profile)
    {
        $uid = $request->get('image_id');
        /** @var Upload $upload */
        $upload = Upload::find($uid);
        if($profile->newAvatar()->first()) {
            $profile->newAvatar()->first()->update([
               'name' => $upload->name,
               'path' => $upload->path,
               'user_id' => $profile->user->id,
               'url' => url('/storage/'.$upload->path)
            ]);
            $profile->update([
                'avatar_accepted' => false,
            ]);
        } else {
            /** @var Image $image */
            $image = $profile->newAvatar()->create([
                'name' => $upload->name,
                'path' => $upload->path,
                'imageable_id' => $profile->id,
                'user_id' => $profile->user->id,
                'url' => url('/storage/'.$upload->path)
            ]);
            $profile->update([
                'new_avatar_id' => $image->id,
                'avatar_accepted' => false
            ]);
        }
        return $profile->newAvatar()->first();
    }

    public function updateBackground(UpdateAvatarRequest $request, Profile $profile)
    {
        $uid = $request->get('image_id');
        /** @var Upload $upload */
        $upload = Upload::find($uid);
        if($profile->newBackground()->first()) {
            $profile->newBackground()->first()->update([
               'name' => $upload->name,
               'path' => $upload->path,
               'user_id' => $profile->user->id,
               'url' => url('/storage/'.$upload->path)
            ]);
            $profile->update([
                'bg_accepted' => false,
            ]);
        } else {
            /** @var Image $image */
            $image = $profile->newBackground()->create([
                'name' => $upload->name,
                'path' => $upload->path,
                'imageable_id' => $profile->id,
                'user_id' => $profile->user->id,
                'url' => url('/storage/'.$upload->path)
            ]);
            $profile->update([
                'new_bg_id' => $image->id,
                'bg_accepted' => false,
            ]);
        }
        return $profile->newBackground()->first();
    }

    public function updateNationalCard(UpdateAvatarRequest $request, Profile $profile)
    {
        $uid = $request->get('image_id');
        /** @var Upload $upload */
        $upload = Upload::find($uid);
        /** @var Image $card */
        $card = $profile->nationalCard()->first();
        if($card) {
            $profile->nationalCard()->first()->update([
               'name' => $upload->name,
               'path' => $upload->path,
               'user_id' => $profile->user->id,
               'url' => url('/storage/'.$upload->path)
            ]);
            $profile->update([
                'national_card_accepted' => false
            ]);
        } else {
            /** @var Image $image */
            $image = $profile->nationalCard()->create([
                'name' => $upload->name,
                'path' => $upload->path,
                'imageable_id' => $profile->id,
                'user_id' => $profile->user->id,
                'url' => url('/storage/'.$upload->path)
            ]);
            $profile->update([
                'national_card_id' => $image->id,
                'national_card_accepted' => false
            ]);
        }
        $profile->save();
        return $profile->nationalCard()->first();
    }

    public function authProjects()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->createdProjects()->get();
    }

    public function userProjects($id)
    {
        /** @var User $auth */
        $auth = $this->findOneOrFail($id);
        return $auth->createdProjects()->get();
    }

    public function userFollowers($id)
    {
        /** @var User $user */
        $user = $this->findOneOrFail($id);
        return $user->followers;
    }

    public function userFollowings($id)
    {
        /** @var User $user */
        $user = $this->findOneOrFail($id);
        return $user->followings;
    }

    public function authReceivedRequests()
    {
        /** @var User $user */
        $user = auth()->user();
        return $this->userReceivedRequests($user->id);
    }

    public function authSentRequests()
    {
        /** @var User $user */
        $user = auth()->user();
        return $this->userSentRequests($user->id);
    }

    public function userReceivedRequests($id)
    {
        /** @var User $user */
        $user = $this->findOneOrFail($id);
        return $user->receivedRequests();
    }

    public function userSentRequests($id)
    {
        /** @var User $user */
        $user = $this->findOneOrFail($id);
        return $user->sentRequests();
    }

    public function sendRequest(SendRequestForProjectRequest $requestForProjectRequest)
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var Project $project */
        $project = Project::find($requestForProjectRequest->project_id);
        /** @var ProjectRequest $request */
        $request = $user->sentRequests()->create([
            'title' => '',
            'price' => $requestForProjectRequest->price,
            'type' => ProjectRequest::FREELANCER_TYPE,
            'delivery_date' => $requestForProjectRequest->delivery_date,
            'description' => $requestForProjectRequest->description,
            'is_distinguished' => $requestForProjectRequest->is_distinguished,
            'is_sponsored' => $requestForProjectRequest->is_sponsored,
            'project_id' => $project->id,
            'to_id' => $project->employer->id,
        ]);
        /** @var Conversation $conversation */
        $conversation = $user->conversations()->create([
            'user_id' => $user->id,
            'to_id' => $project->employer->id,
            'project_id' => $project->id,
            'status' => Conversation::OPEN_STATUS,
        ]);
        $project->update([
            'conversation_id' => $conversation->id,
        ]);
        /** @var User $admin */
        $admin = User::all()->filter(function(User $user) {
            return $user->hasRole('admin');
        })->first();
        $body = 'توضیحات فریلنسر: پیشنهاد "'.$request->price.'" تومان در "'. $request->delivery_date .'" روز ';
        $message = Message::create([
            'user_id' => $admin->id,
            'type' => Message::TEXT_TYPE,
            'conversation_id' => $conversation->id,
            'body' => $body,
            'is_system' => true
        ]);
        $conversation->new_messages_count = $conversation->new_messages_count + 1;
        $conversation->save();
        broadcast(new NewConversationEvent($user, $conversation));
        broadcast(new NewConversationEvent($project->employer, $conversation));
        $employer = $project->employer;
        $nonce = $this->getNonce();
        broadcast(new NewMessageNotificationEvent($employer, $employer->newMessagesCount(), $nonce));
        $nonce = $this->getNonce();
        broadcast(new NewMessageNotificationEvent($user, $user->newMessagesCount(), $nonce));
        $nonce = $this->getNonce();
        broadcast(new NewMessageNotificationEvent($admin, $admin->newMessagesCount(), $nonce));
        broadcast(new NewMessageEvent($message, $user));
        broadcast(new NewMessageEvent($message, $project->employer));
        broadcast(new NewMessageEvent($message, $admin));
        if($requestForProjectRequest->has('new_secure_payments')) {
            $securePayments = $requestForProjectRequest->get('new_secure_payments');
            foreach ($securePayments as $payment) {
                $request->securePayments()->create([
                    'title' => $payment['title'],
                    'price' => $payment['price'],
                    'status' => SecurePayment::CREATED_STATUS,
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'to_id' => $project->employer->id,
                    'is_first' => true,
                ]);
                $body = 'پرداخت امن "' . $payment['title'] . '" : "' . $payment['price'] . '" ریال "';
                $message = Message::create([
                    'user_id' => $admin->id,
                    'type' => Message::TEXT_TYPE,
                    'conversation_id' => $conversation->id,
                    'body' => $body,
                    'is_system' => true
                ]);
                $employer = $project->employer;
                $nonce = $this->getNonce();
                broadcast(new NewMessageNotificationEvent($user, $user->newMessagesCount(), $nonce));
                $nonce = $this->getNonce();
                broadcast(new NewMessageNotificationEvent($admin, $admin->newMessagesCount(), $nonce));
                $nonce = $this->getNonce();
                broadcast(new NewMessageNotificationEvent($employer, $employer->newMessagesCount(), $nonce));
                broadcast(new NewMessageEvent($message, $user));
                broadcast(new NewMessageEvent($message, $employer));
                broadcast(new NewMessageEvent($message, $admin));
            }
        }
        if($user->number > $user->requests_count) {
            $user->update([
                'requests_count' => $user->requests_count + 1,
            ]);
        }
        /** @var Wallet $wallet */
        $wallet = $user->wallet;
        /** @var Setting $settings */
        $settings = Setting::all()->first();
        if($requestForProjectRequest->is_distinguished) {
            $wallet->forceWithdraw((int) $settings->distinguished_price);
        }
        if($requestForProjectRequest->is_sponsored) {
            $wallet->forceWithdraw((int) $settings->sponsored_price);
        }
        $user->save();
        return $request;
    }

    public function getReceivedRequest($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $request = $auth->receivedRequests()->findOrFail($id);
        return $request;
    }

//    public function acceptOrRejectReceivedRequest(AcceptOrRejectProjectRequest $projectRequest, ProjectRequest $request)
//    { /** @var User $auth */
//
//        $request->update([
//            'status' => $projectRequest->accepted ? ProjectRequest::ACCEPTED_STATUS : ProjectRequest::REJECTED_STATUS
//        ]);
//        if($projectRequest->accepted) {
//            $this->rejectProjectOtherRequests($request);
//        }
//        return $request;
//    }

    public function authProjectRequests($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Project $project */
        $project = $auth->createdProjects()->findOrFail($id);
        return $project->requests()->where('type', '=', ProjectRequest::FREELANCER_TYPE)
            ->where('status', '=', ProjectRequest::CREATED_STATUS)->get();
    }

    public function authAcceptOrRejectProjectRequest(AcceptOrRejectProjectRequest $projectRequest, Project $project, ProjectRequest $request)
    {
        /** @var User $user */
        $user = $request->user;
        if($projectRequest->accepted) {
            $request->update([
                'status' => ProjectRequest::ACCEPTED_STATUS
            ]);
            $request->save();
            AcceptFreelancerRequest::create([
                'employer_id' => $project->employer->id,
                'freelancer_id' => $user->id,
                'project_id' => $project->id,
                'request_id' => $request->id,
                'status' => AcceptFreelancerRequest::CREATED_STATUS
            ]);
            $body = 'درخواست شما برای پروژه ی "'.$project->title.'" تایید شد';
            $notificationBody = 'درخواست کاربر "'.$user->username.'" برای پروژه ی "'.$project->title.'" تایید شد';
            $project->notifications()->create(array(
                'text' => $body,
                'type' => Notification::PROJECT,
                'user_id' => $user->id,
                'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
            ));
            $admins = User::all()->filter(function (User $u){
                return $u->hasRole('admin');
            })->pluck('id');
            $ids = collect([$user->id]);
            $ids2 = collect($admins->values());
            $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
            $emails2 = User::all()->whereIn('id', $ids2->toArray())->pluck('email');
            $users = User::all()->whereIn('id', $ids->toArray());
            Notification::sendNotificationToAll($emails->toArray(), $notificationBody, $notificationBody, null);
            Notification::sendNotificationToAll($emails2->toArray(), $notificationBody, $notificationBody, null);
            Notification::sendNotificationToUsers($users);
            Notification::sendNotificationToUsers(collect([$user]));
        } else {
            $request->update([
                'status' => ProjectRequest::REJECTED_STATUS
            ]);
            if($project->selected_request_id == $request->id) {
                $project->update([
                    'selected_request_id' => null,
                    'request_select_date' => null,
                    'conversation_id' => null,
                    'freelancer_id' => null,
                ]);
            }
            $body = 'درخواست شما برای پروژه ی "'.$project->title.'" رد شد';
            $notificationBody = 'درخواست کاربر "'.$user->username.'" برای پروژه ی "'.$project->title.'" رد شد';
            $project->notifications()->create(array(
                'text' => $body,
                'type' => Notification::PROJECT,
                'user_id' => $user->id,
                'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
            ));
            $admins = User::all()->filter(function (User $u){
                return $u->hasRole('admin');
            })->pluck('id');
            $ids = collect([]);
            $ids2 = collect([]);
            $ids->push($admins->values());
            $ids2->push($user->id);
            $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
            $emails2 = User::all()->whereIn('id', $ids2->toArray())->pluck('email');
            $users = User::all()->whereIn('id', $ids->toArray());
            Notification::sendNotificationToAll($emails->toArray(), $notificationBody, $notificationBody, null);
            Notification::sendNotificationToAll($emails2->toArray(), $notificationBody, $notificationBody, null);
            Notification::sendNotificationToUsers($users);
            Notification::sendNotificationToUsers(collect([$user]));
        }
        $project->save();
        return $request;
    }
    public function freelancerAcceptOrRejectRequest(AcceptOrRejectProjectRequest $projectRequest, Project $project, AcceptFreelancerRequest $freelancerRequest)
    {
        if($projectRequest->accepted) {
            $user = $project->employer;

            /** @var Conversation $conversation */
            $conversation = Conversation::where('project_id', '=', $project->id)->where('user_id', '=', $freelancerRequest->freelancer->id)->first();
            $project->update([
                'selected_request_id' => $freelancerRequest->request->id,
                'request_select_date' => Carbon::now(),
                'conversation_id' => $conversation->id,
                'status' => Project::STARTED_STATUS,
                'freelancer_id' => $freelancerRequest->freelancer->id
            ]);
            $freelancerRequest->update([
               'status' => AcceptFreelancerRequest::ACCEPTED_STATUS
            ]);
            /** @var ProjectRequest $request */
            $request = $freelancerRequest->request;
            $request->update([
                'status' => ProjectRequest::STARTED_STATUS
            ]);
            $request->save();
            $this->acceptSecurePayments($freelancerRequest->request);
            $this->rejectProjectOtherRequests($freelancerRequest->request);
            $body = 'فریلنسر درخواست شما برای پروژه ی "'.$project->title.'" را تایید کرد';
            $notificationBody = 'درخواست کارفرما "'.$project->employer->username.'" برای پروژه ی "'.$project->title.'" توسط فریلنسر تایید شد';
            $project->notifications()->create(array(
                'text' => $body,
                'type' => Notification::PROJECT,
                'user_id' => $user->id,
                'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
            ));
            $admins = User::all()->filter(function (User $u){
                return $u->hasRole('admin');
            })->pluck('id');
            $ids = collect($admins->values());
            $ids2 = collect([]);
            foreach ($ids as $id) {
                $project->notifications()->create(array(
                    'text' => $notificationBody,
                    'type' => Notification::PROJECT,
                    'user_id' => $id,
                    'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
                ));
            }
            $ids2->push($user->id);
            $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
            $emails2 = User::all()->whereIn('id', $ids2->toArray())->pluck('email');
            $users = User::all()->whereIn('id', $ids->toArray());
            Notification::sendNotificationToAll($emails->toArray(), $notificationBody, $notificationBody, null);
            Notification::sendNotificationToAll($emails2->toArray(), $notificationBody, $notificationBody, null);
            Notification::sendNotificationToUsers($users);
            Notification::sendNotificationToUsers(collect([$user]));
        } else {
            /** @var ProjectRequest $req */
            $req = $freelancerRequest->request;
            $freelancerRequest->forceDelete();
            $req->update([
                'status' => ProjectRequest::REJECTED_STATUS,
            ]);
            $req->securePayments()->delete();
            if($project->selected_request_id == $req->id) {
                $project->update([
                    'selected_request_id' => null,
                    'request_select_date' => null,
                    'conversation_id' => null,
                    'freelancer_id' => null,
                ]);
            }
            $user = $project->employer;
            $body = 'فریلنسر درخواست شما برای پروژه ی "'.$project->title.'" را رد کرد';
            $notificationBody = 'درخواست کارفرما "'.$user->username.'" برای پروژه ی "'.$project->title.'" توسط فریلنسر رد شد';
            $project->notifications()->create(array(
                'text' => $body,
                'type' => Notification::PROJECT,
                'user_id' => $user->id,
                'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
            ));
            $admins = User::all()->filter(function (User $u){
                return $u->hasRole('admin');
            })->pluck('id');
            $ids = collect([]);
            $ids2 = collect([]);
            $ids->push($admins->values());
            foreach ($ids as $id) {
                $project->notifications()->create(array(
                    'text' => $notificationBody,
                    'type' => Notification::PROJECT,
                    'user_id' => $id,
                    'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
                ));
            }
            $ids2->push($user->id);
            $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
            $emails2 = User::all()->whereIn('id', $ids2->toArray())->pluck('email');
            $users = User::all()->whereIn('id', $ids->toArray());
            Notification::sendNotificationToAll($emails->toArray(), $notificationBody, $notificationBody, null);
            Notification::sendNotificationToAll($emails2->toArray(), $notificationBody, $notificationBody, null);
            Notification::sendNotificationToUsers($users);
            Notification::sendNotificationToUsers(collect([$user]));
        }
        $project->save();
        return $freelancerRequest;
    }

    public function deposit($amount)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Wallet $wallet */
       try {
            $invoice = new Invoice;
             $invoice->amount((int) ($amount / 10));
            $invoice->detail(['detailName' => 'your detail goes here']);
            $invoice->detail('detailName','your detail goes here');
            $invoice->detail(['name1' => 'detail1','name2' => 'detail2']);
            $invoice->detail('detailName1','your detail1 goes here')
                ->detail('detailName2','your detail2 goes here');
            $invoice->detail('t_id', $invoice->getTransactionId());
            return Payment::purchase($invoice, function($driver, $transactionId) use($auth, $invoice){
                $t = Transaction::create([
                    'user_id' =>  $auth->id,
                    'transaction_id' => $transactionId,
                    'type' => Transaction::DEPOSIT_TYPE,
                    'status' => Transaction::CREATED_STATUS,
                    'amount' => $invoice->getAmount(),
                ]);
                $auth->histories()->create([
                    'status' => PaymentHistory::CREATED_STATUS,
                    'type' => PaymentHistory::DEPOSIT_TYPE,
                    'history_id' => $t->id,
                    'amount' => (int) $invoice->getAmount() * 10,
                ]);
            })->pay()->toJson();
        } catch (UnacceptedTransactionException $e) {
            return response()->json(['success' => false, 'message' => 'متاسفانه خطایی رخ داده است.'], 500);
        }
    }

    public function withdraw($amount)
    {
        /** @var User $auth */
        $auth = auth()->user();
        try {
            $w = $auth->withdraws()->create([
                'amount' => (int) $amount,
                'status' => WithdrawRequest::CREATED_STATUS,
            ]);
            $auth->histories()->create([
               'status' => PaymentHistory::CREATED_STATUS,
               'type' => PaymentHistory::WITHDRAW_TYPE,
               'history_id' => $w->id,
               'amount' => (int) $amount
            ]);
            return true;
        } catch (UnacceptedTransactionException $e) {
            return false;
        }
    }

    public function wallet()
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Wallet $wallet */
        $wallet = $auth->wallet;
        return $wallet;
    }

    public function ownLikedPosts()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $this->userLikedPosts($auth->id);
    }

    public function userLikedPosts($id)
    {
        /** @var User $user */
        $user = $this->findOneOrFail($id);
        $posts_id = $user->likes()->withType(Post::class)->pluck('likeable_id');
        return Post::whereIn('id',$posts_id)->get();
    }

    public function ownSavedPosts()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $this->userSavedPosts($auth->id);
    }

    public function userSavedPosts($id)
    {
        /** @var User $user */
        $user = $this->findOneOrFail($id);
        $posts_id = $user->favorites()->withType(Post::class)->pluck('favoriteable_id');
        return Post::whereIn('id',$posts_id)->get();
    }

    public function ownBookmarkedPosts()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $this->userBookmarkedPosts($auth->id);
    }

    public function ownFollowingsPosts()
    {
        /** @var User $auth */
        $auth = auth()->user();
        $followings = $auth->followings;
        return Post::whereIn('user_id', $followings->pluck('id'))->get();
    }

    public function userBookmarkedPosts($id)
    {
        /** @var User $user */
        $user = $this->findOneOrFail($id);
        $posts_id = $user->bookmarks()->withType(Post::class)->pluck('model_id');
        return Post::whereIn('id',$posts_id)->get();
    }

    public function authPortfolios()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $this->userPortfolios($auth->id);
    }

    public function userPortfolios($id)
    {
        /** @var User $auth */
        $auth = $this->findOneOrFail($id);
        return $auth->portfolios()->get();
    }

    public function blockAndUnblockUser($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var User $user */
        $user = $auth->blockedUsers()->find($id);
        if(!$user) {
            $user = $this->findOneOrFail($id);
            $auth->blockedUsers()->save($user);
            return true;
        } else {
            $auth->blockedUsers()->detach($user);
            return false;
        }
    }

    public function blockedUsers()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->blockedUsers()->get();
    }

    private function setIntroducer(RegisterRequest $request, User $user)
    {
        if($request->has('introducer')) {
            /** @var User $introducer */
            $introducer = $this->findOneBy(['username' => $request->get('introducer')]);
            if($introducer && $introducer->hasRole('freelancer')) {
                $introducer->introduces()->save($user);
                if($introducer->introduces()->count() > 9) {
                    $introducer->is_businessed = true;
                    $introducer->save();
                }
            }
        }
        return $user;
    }

    public function disputes()
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Collection $disputes */
        return Dispute::where('freelancer_id', '=', $auth->id)->orWhere('employer_id', '=', $auth->id)->get();
    }

    public function authNotifications(): Collection
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->notifs()->orderByDesc('created_at')->get();
    }

    public function seenNotifications()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->update([
            'new_notifications' => 0,
        ]);
    }

    public function authCanDoProjects()
    {
        /** @var User $auth */
        $auth = auth()->user();
        $auth_skills = $auth->skills()->pluck('skills.id as id');
        return Project::query()->with(['skills', 'employer', 'freelancer'])
            ->where(function ($q) use($auth_skills){
                $q->where('status', '=', Project::PUBLISHED_STATUS);
                $q->where('created_at', '>=', Carbon::now()->subDays(14));
                $q->whereHas('skills', function ($query) use($auth_skills) {
                    $query->whereIn('skills.id', $auth_skills);
                });
        })->orderByDesc('created_at')->get();
    }

    public function acceptOrRejectAvatar(AcceptOrRejectProjectRequest $request, Profile $profile)
    {
        /** @var User $user */
        $user = $profile->user;
        $admins = User::all()->filter(function (User $u){
            return $u->hasRole('admin');
        })->pluck('id');
        $ids = collect($admins->values());
        $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
        $users = User::all()->whereIn('id', $ids->toArray());
        if($request->accepted) {
            $profile->update([
                'avatar_accepted' => true,
                'avatar_id' => $profile->new_avatar_id
            ]);
            $user->notifs()->create(array(
                'text' => 'عکس پروفایل شما تایید شد',
                'type' => Notification::EMPLOYER,
                'user_id' => $user->id,
                'image_id' => null
            ));
            foreach ($ids as $id) {
                $user->notifs()->create(array(
                    'text' => 'عکس پروفایل کاربر '.$user->username.' تایید شد',
                    'type' => Notification::EMPLOYER,
                    'user_id' => $id,
                    'image_id' => null,
                ));
            }
            Notification::sendNotificationToUsers(collect([$user]));
            Notification::sendNotificationToAll($emails->toArray(), 'عکس پروفایل کاربر '.$user->username.' تایید شد', 'عکس پروفایل کاربر '.$user->username.' تایید شد', null);
            Notification::sendNotificationToUsers($users);
            return true;
        } else {
            $profile->newAvatar()->delete();
            $profile->update([
                'avatar_accepted' => false,
                'new_avatar_id' => null
            ]);
            $user->notifs()->create(array(
                'text' => 'عکس پروفایل شما تایید نشد',
                'type' => Notification::PROJECT,
                'user_id' => $user->id,
                'image_id' => null
            ));
            foreach ($ids as $id) {
                $user->notifs()->create(array(
                    'text' => 'عکس پروفایل کاربر '.$user->username.' تایید نشد',
                    'type' => Notification::PROJECT,
                    'user_id' => $id,
                    'image_id' => null
                ));
            }
            Notification::sendNotificationToUsers(collect([$user]));
            Notification::sendNotificationToAll($emails->toArray(), 'عکس پروفایل کاربر '.$user->username.' تایید نشد', 'عکس پروفایل کاربر '.$user->username.' تایید نشد', null);
            Notification::sendNotificationToUsers($users);
            return false;
        }
    }

    public function acceptOrRejectBackground(AcceptOrRejectProjectRequest $request, Profile $profile)
    {
        /** @var User $user */
        $user = $profile->user;
        $admins = User::all()->filter(function (User $u){
            return $u->hasRole('admin');
        })->pluck('id');
        $ids = collect($admins->values());
        $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
        $users = User::all()->whereIn('id', $ids->toArray());

        if($request->accepted) {
            $profile->update([
                'bg_accepted' => true,
                'bg_id' => $profile->new_bg_id
            ]);
            $user->notifs()->create(array(
                'text' => 'عکس پس زمینه شما تایید شد',
                'type' => Notification::EMPLOYER,
                'user_id' => $user->id,
                'image_id' => null
            ));
            foreach ($ids as $id) {
                $user->notifs()->create(array(
                    'text' => 'عکس پس زمینه کاربر '.$user->username.' تایید شد',
                    'type' => Notification::EMPLOYER,
                    'user_id' => $id,
                    'image_id' => null,
                ));
            }
            Notification::sendNotificationToUsers(collect([$user]));
            Notification::sendNotificationToAll($emails->toArray(), 'عکس پروفایل کاربر '.$user->username.' تایید شد', 'عکس پروفایل کاربر '.$user->username.' تایید شد', null);
            Notification::sendNotificationToUsers($users);

            return true;
        } else {
            $profile->newBackground()->delete();
            $profile->update([
                'bg_accepted' => false,
                'new_bg_id' => null
            ]);
            $user->notifs()->create(array(
                'text' => 'عکس پس زمینه شما تایید نشد',
                'type' => Notification::PROJECT,
                'user_id' => $user->id,
                'image_id' => null
            ));
            foreach ($ids as $id) {
                $user->notifs()->create(array(
                    'text' => 'عکس پس زمینه کاربر '.$user->username.' تایید نشد',
                    'type' => Notification::PROJECT,
                    'user_id' => $id,
                    'image_id' => null
                ));
            }
            Notification::sendNotificationToUsers(collect([$user]));
            Notification::sendNotificationToAll($emails->toArray(), 'عکس پروفایل کاربر '.$user->username.' تایید نشد', 'عکس پروفایل کاربر '.$user->username.' تایید نشد', null);
            Notification::sendNotificationToUsers($users);
            return false;
        }
    }

    public function acceptOrRejectNationalCard(AcceptOrRejectProjectRequest $request, Profile $profile)
    {
        $profile->update([
            'national_card_accepted' => (bool)$request->accepted
        ]);
        if(!$request->accepted) {
            $profile->nationalCard()->delete();
            $profile->update([
                'national_card_id' => null
            ]);
        }
        /** @var User $user */
        $user = $profile->user;
        $admins = User::all()->filter(function (User $u){
            return $u->hasRole('admin');
        })->pluck('id');
        $ids = collect($admins->values());
        $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
        $users = User::all()->whereIn('id', $ids->toArray());
        $user->notifs()->create(array(
            'text' => $request->accepted ? 'عکس کارت ملی شما تایید شد' : 'عکس کارت ملی شما تایید نشد',
            'type' => Notification::PROJECT,
            'user_id' => $user->id,
            'image_id' => null
        ));
        foreach ($ids as $id) {
            $user->notifs()->create(array(
                'text' => $request->accepted ? 'عکس کارت ملی کاربر '.$user->username.' تایید شد' : 'عکس کارت ملی کاربر '.$user->username.' تایید نشد',
                'type' => Notification::PROJECT,
                'user_id' => $id,
                'image_id' => null
            ));
        }
        Notification::sendNotificationToUsers(collect([$user]));
        Notification::sendNotificationToAll($emails->toArray(),
            $request->accepted ? 'عکس کارت ملی کاربر '.$user->username.' تایید شد' : 'عکس کارت ملی کاربر '.$user->username.' تایید نشد',
            $request->accepted ? 'عکس کارت ملی کاربر '.$user->username.' تایید شد' : 'عکس کارت ملی کاربر '.$user->username.' تایید نشد',
            null);
        Notification::sendNotificationToUsers($users);
        dispatch(new ValidateUserJob($profile->user));
        return (bool)$request->accepted;
    }

    public function acceptOrRejectSheba(AcceptOrRejectProjectRequest $request, Profile $profile)
    {
        $profile->update([
            'sheba_accepted' => $request->accepted ? true : false
        ]);
        if(!$request->accepted) {
            $profile->update([
                'sheba' => null
            ]);
        }
        /** @var User $user */
        $user = $profile->user;
        $admins = User::all()->filter(function (User $u){
            return $u->hasRole('admin');
        })->pluck('id');
        $ids = collect($admins->values());
        $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
        $users = User::all()->whereIn('id', $ids->toArray());
        $user->notifs()->create(array(
            'text' => $request->accepted ? 'شماره شبا شما تایید شد' : 'شماره شبا شما تایید نشد',
            'type' => Notification::PROJECT,
            'user_id' => $user->id,
            'image_id' => null
        ));
        foreach ($ids as $id) {
            $user->notifs()->create(array(
                'text' => $request->accepted ? 'شماره شبا کاربر '.$user->username.' تایید شد' : 'شماره شبا کاربر '.$user->username.' تایید نشد',
                'type' => Notification::PROJECT,
                'user_id' => $id,
                'image_id' => null
            ));
        }
        Notification::sendNotificationToUsers(collect([$user]));
        Notification::sendNotificationToAll($emails->toArray(),
            $request->accepted ? 'شماره شبا کاربر '.$user->username.' تایید شد' : 'شماره شبا کاربر '.$user->username.' تایید نشد',
            $request->accepted ? 'شماره شبا کاربر '.$user->username.' تایید شد' : 'شماره شبا کاربر '.$user->username.' تایید نشد',
            null);
        Notification::sendNotificationToUsers($users);
        return $request->accepted ? true : false;
    }

    public function updateSheba(UpdateShebaRequest $request, Profile $profile)
    {
        $updated = $profile->update([
            'sheba' => $request->sheba,
            'sheba_accepted' => false
        ]);
        return $updated;
    }

    public function changeAuthPassword(UpdatePasswordRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->update([
            'password' => bcrypt($request->get('new_password'))
        ]);
    }

    public function paymentHistories()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->histories()->get();
//        return $auth->withdraws()->get()->filter(function (WithdrawRequest $request) {
//            return $request->status == WithdrawRequest::PAYED_STATUS || $request->status == WithdrawRequest::REJECTED_STATUS;
//        });
//        return Notification::all()->where('type', '=', Notification::PAYMENT_PAYED_TYPE)->where('user_id', '=', $auth->id);
    }

    public function monthlyIncome()
    {
        /** @var User $auth */
        $auth = auth()->user();
        $sum = $auth->monthlyIncome();
        return response()->json(['data' => ['income' => $sum],]);
    }

    public function ownCreatedSecurePayments()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->sentSecurePayments()->where('status', '=', SecurePayment::CREATED_STATUS)->get();
    }

    public function ownAcceptedSecurePayments()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->sentSecurePayments()->where('status', '=', SecurePayment::ACCEPTED_STATUS)->get();
    }

    public function ownPayedSecurePayments()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->sentSecurePayments()->where('status', '=', SecurePayment::PAYED_STATUS)->sum('price');
    }

    public function ownFreeSecurePayments()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->sentSecurePayments()->where('status', '=', SecurePayment::FREE_STATUS)->get();
    }

    public function ownCreatedWithdraws()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->withdraws()->where('status', '=', WithdrawRequest::CREATED_STATUS)->get();
    }

    public function ownPayedWithdraws()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->withdraws()->where('status', '=', WithdrawRequest::PAYED_STATUS)->get();
    }

    public function ownRejectedWithdraws()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->withdraws()->where('status', '=', WithdrawRequest::REJECTED_STATUS)->get();
    }

    private function rejectProjectOtherRequests(ProjectRequest $request) {
        $otherRequests = $request->project->requests()->where('id', '!=', $request->id);
        $otherRequests->update([
            'status' => ProjectRequest::REJECTED_STATUS,
        ]);
    }

    private function acceptSecurePayments(ProjectRequest $request)
    {
        $request->update([
            'status' => ProjectRequest::ACCEPTED_STATUS,
        ]);
//        $payments = SecurePayment::where('request_id', '=', $request->id);
//        $payments->update([
//            'status' => ProjectRequest::ACCEPTED_STATUS,
//        ]);
    }

    public function seenMessage(SeenMessageRequest $seenMessageRequest)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Message $message */
        $message = Message::findOrFail($seenMessageRequest->message_id);
        if($auth->id != $message->user_id) {
            /** @var Conversation $conversation */
            $conversation = $message->conversation;
            $message->update([
                'seen' => true
            ]);
            $conversation->messages()->where('user_id', '!=', $auth->id)
                ->where('created_at', '<=', $message->created_at)->update([
                    'seen' => true
                ]);
            $nonce = $this->getNonce();
            broadcast(new NewMessageNotificationEvent($auth, $auth->newMessagesCount(), $nonce));
            return true;
        } else {
            return false;
        }
    }

    public function seenConversation(SeenConversationRequest $seenConversationRequest)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Conversation $conversation */
        $conversation = Conversation::findOrFail($seenConversationRequest->conversation_id);
        $conversation->messages()->where('user_id', '!=', $auth->id)
            ->where('created_at', '<=', Carbon::now())->update([
                'seen' => true
            ]);
        $nonce = $this->getNonce();
        broadcast(new NewMessageNotificationEvent($auth, $auth->newMessagesCount(), $nonce));
        return true;
    }

    public function authNewMessagesCount()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth->newMessagesCount();
    }
}
