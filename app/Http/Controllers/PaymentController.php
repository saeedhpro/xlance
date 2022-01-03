<?php

namespace App\Http\Controllers;

use App\Events\NewConversationEvent;
use App\Events\NewMessageEvent;
use App\Events\NewMessageNotificationEvent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\PaymentHistory;
use App\Models\Project;
use App\Models\Request as ProjectRequest;
use App\Models\RequestPackage;
use App\Models\SecurePayment;
use App\Models\SelectedPlan;
use App\Models\TempSecurePayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use MannikJ\Laravel\Wallet\Models\Wallet;
use Shetabit\Payment\Facade\Payment;

class PaymentController extends Controller
{
    public function verify(Request $request)
    {
        /** @var Transaction $t */
        $t = Transaction::where('transaction_id', '=', $request->get('Authority'))->first();
        try{
            $receipt = Payment::amount((int) $t->amount)->transactionId($t->transaction_id)->verify();
            $referenceId = $receipt->getReferenceId();
            if($t->type == Transaction::DEPOSIT_TYPE) {
                /** @var User $user */
                $user = $t->user;
                /** @var Wallet $w */
                $w = $user->wallet;
                $w->deposit(((int) $t->amount) * 10);
                $t->update([
                    'status' => Transaction::PAYED_STATUS
                ]);
                /** @var PaymentHistory $history */
                $history = PaymentHistory::where('type', '=', PaymentHistory::DEPOSIT_TYPE)->where('history_id', '=', $t->id)->first();
                $history->update([
                    'status' => PaymentHistory::DEPOSITED_STATUS,
                ]);
                $history->save();
                $admins = User::query()->with('roles')->whereHas('roles', function ($q) {
                    $q->where('name', '=', 'admin');
                })->get();
                foreach ($admins as $admin) {
                    $user->notifs()->create([
                        'text' => "افزایش اعتبار $user->first_name $user->last_name به مبلغ $t->amount با موفقیت انجام شده است ",
                        'type' => Notification::ADMIN_RECORDS,
                        'user_id' => $admin->id,
                        'image_id' => null
                    ]);
                    Notification::sendNotificationToUsers(collect([$admin]));
                }
                $status = 200;
                $message = 'پرداخت با موفقیت انجام شد برای ادامه روی دکمه ی بازگشت کلیک کنید';
                $url = 'https://xlance.ir/records?referenceId='.$referenceId;
                return view('payment', compact('referenceId', 'status', 'message', 'url'));
            } else if($t->type == Transaction::PROJECT_TYPE) {
                /** @var User $user */
                $user = $t->user;
                /** @var Project $project */
                $project = $user->createdProjects()->where('id', '=', $t->project->id)->first();
                $project->update([
                    'status' => Project::CREATED_STATUS,
                ]);
                /** @var Wallet $w */
                $w = $user->wallet;
                $w->forceWithdraw((int) $t->withdraw_amount);
                $t->update([
                    'status' => Transaction::PAYED_STATUS
                ]);
                if($project->freelancer) {
                    /** @var Conversation $conversation */
                    $conversation = $user->conversations()->create([
                        'user_id' => $project->employer->id,
                        'to_id' => $project->freelancer->id,
                        'status' => Conversation::OPEN_STATUS,
                    ]);
                    broadcast(new NewConversationEvent($user, $conversation));
                }
                $project->notifications()->create([
                    'text' => 'پروژه '. $project->title .' ایجاد شد.',
                    'type' => Notification::PROJECT,
                    'user_id' => $user->id,
                    'notifiable_id' => $project->id,
                    'image_id' => null
                ]);
                Notification::sendNotificationToUsers(collect([$user]));
                $admins = User::query()->with('roles')->whereHas('roles', function ($q) {
                    $q->where('name', '=', 'admin');
                })->get();
                $employer = $project->employer;
                $freelancer = $project->freelancer;
                foreach ($admins as $admin) {
                    $user->notifs()->create([
                        'text' =>
                        $freelancer ? "$employer->first_name $employer->last_name پروژه ی $project->title را برای $freelancer->first_name $freelancer->last_name ایجاد کرد."
                        : "$employer->first_name $employer->last_name پروژه ی $project->title را ایجاد کرد.",
                        'type' => Notification::ADMIN_PROJECT,
                        'user_id' => $admin->id,
                        'image_id' => null
                    ]);
                    Notification::sendNotificationToUsers(collect([$admin]));
                }
                $status = 200;
                $message = 'پرداخت با موفقیت انجام شد برای ادامه روی دکمه ی بازگشت کلیک کنید';
                $url = 'https://xlance.ir/projects/'.$project->id.'?referenceId='.$referenceId;
                return view('payment', compact('referenceId', 'status', 'message', 'url'));
            } else if($t->type == Transaction::PACKAGE_TYPE) {
                /** @var User $user */
                $user = $t->user;
                $monthly = $t->is_monthly;
                $package = $t->package;;
                $lastPlan = SelectedPlan::where('user_id', '=', $user->id)->get()
                    ->last();
                if($lastPlan) {
                    $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $lastPlan->end_date)->addSecond();
                    $end_date = $monthly ? Carbon::createFromFormat('Y-m-d H:i:s', $lastPlan->end_date)->addSecond()->addMonth() :
                        Carbon::createFromFormat('Y-m-d H:i:s', $lastPlan->end_date)->addSecond()->addYear();
                } else {
                    $start_date = Carbon::now();
                    $end_date = $monthly ? Carbon::now()->addMonth() : Carbon::now()->addYear();
                }
                SelectedPlan::create([
                    'user_id' => $user->id,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'number' => $package->number,
                    'title' => $package->title,
                    'plan_id' => $package->id,
                    'is_monthly' => $monthly
                ]);
                /** @var Wallet $wallet */
                $wallet = $user->wallet;
                $amount = $monthly ? (int) $package->price - $t->amount :
                    ((12 * $package->price) * 80 / 100) - ($t->amount * 10);
                if($amount > $wallet->balance) {
                    $wallet->forceWithdraw($wallet->balance);
                } else {
                    $wallet->forceWithdraw($amount);
                }
                $status = 200;
                $t->update([
                    'status' => Transaction::PAYED_STATUS
                ]);
                $user->notifs()->create([
                    'text' => 'ارتقای عضویت شما به پکیج '. $package->title .' با موفقیت انجام شد.',
                    'type' => Notification::PACKAGE,
                    'user_id' => $user->id,
                    'notifiable_id' => $user->id,
                    'image_id' => null
                ]);
                Notification::sendNotificationToUsers(collect([$user]));
                $admins = User::query()->with('roles')->whereHas('roles', function ($q) {
                    $q->where('name', '=', 'admin');
                })->get();
                foreach ($admins as $admin) {
                    $user->notifs()->create([
                        'text' => 'ارتقای عضویت شما به پکیج '. $package->title .' با موفقیت انجام شد.',
                        'type' => Notification::ADMIN_PACKAGE,
                        'user_id' => $admin->id,
                        'image_id' => null
                    ]);
                    Notification::sendNotificationToUsers(collect([$admin]));
                }
                $message = 'پرداخت با موفقیت انجام شد برای ادامه روی دکمه ی بازگشت کلیک کنید';
                $url = 'https://xlance.ir/membership-upgrade/?referenceId='.$referenceId.'&id='.$package->id;
                return view('payment', compact('referenceId', 'status', 'message', 'url'));
            } else if($t->type == Transaction::SECURE_PAYMENT_TYPE) {
                /** @var SecurePayment $securePayment */
                $securePayment = $t->securePayment;
                $securePayment->update([
                   'status' => SecurePayment::PAYED_STATUS
                ]);
                /** @var Project $project */
                $project = $t->project;
                $status = 200;
                $t->update([
                    'status' => Transaction::PAYED_STATUS
                ]);
                $admins = User::query()->with('roles')->whereHas('roles', function ($q) {
                    $q->where('name', '=', 'admin');
                })->get();
                $freelancer = $project->freelancer;
                foreach ($admins as $admin) {
                    $project->notifications()->create([
                        'text' => $freelancer->first_name . '' . $freelancer->last_name . 'برای پروژه ' . $project->title . 'پرداخت امن ' . $t->amount . 'ایجاد کرده است',
                        'type' => Notification::ADMIN_PROJECT,
                        'user_id' => $admin->id,
                        'image_id' => null
                    ]);
                    Notification::sendNotificationToUsers(collect([$admin]));
                }
                $message = 'پرداخت با موفقیت انجام شد برای ادامه روی دکمه ی بازگشت کلیک کنید';
                $url = 'https://xlance.ir/projects/'. $project->id .'?referenceId='.$referenceId.'&id='.$securePayment->id;
                return view('payment', compact('referenceId', 'status', 'message', 'url'));
            } else if($t->type == Transaction::REQUEST_TYPE) {
                /** @var Project $project */
                $project = $t->project;
                /** @var ProjectRequest $req */
                $req = $t->request;
                $user = $req->user;
                $req->update([
                    'status' => ProjectRequest::CREATED_STATUS,
                ]);
                $req->save();

                $this->createSecurePayments($req, $user, $project);

                $status = 200;
                $t->update([
                    'status' => Transaction::PAYED_STATUS
                ]);
                $message = 'پرداخت با موفقیت انجام شد برای ادامه روی دکمه ی بازگشت کلیک کنید';
                $url = 'https://xlance.ir/projects/'. $project->id .'?referenceId='.$referenceId;
                return view('payment', compact('referenceId', 'status', 'message', 'url'));
            }
        } catch (\Exception $e){
            if($t->type == Transaction::DEPOSIT_TYPE) {
                $t->update([
                    'status' => Transaction::CANCELED_STATUS
                ]);
                /** @var PaymentHistory $history */
                $history = PaymentHistory::where('type', '=', PaymentHistory::DEPOSIT_TYPE)->where('history_id', '=', $t->id)->first();
                $history->update([
                    'status' => PaymentHistory::REJECTED_STATUS,
                ]);
                $history->save();
            }
            $status = $e->getCode();
            $message = $e->getMessage();
            $url = 'https://xlance.ir/';
            $referenceId = '';
            return view('payment', compact('referenceId', 'status', 'message', 'url'));
        }
    }

    private function createSecurePayments(ProjectRequest $req, User $user, Project $project)
    {
        /** @var Conversation $conversation */
        $conversation = $user->conversations()->create([
            'user_id' => $user->id,
            'to_id' => $project->employer->id,
            'project_id' => $project->id,
            'status' => Conversation::OPEN_STATUS,
        ]);
        /** @var User $admin */
        $admin = User::all()->filter(function(User $user) {
            return $user->hasRole('admin');
        })->first();
        $body = 'توضیحات فریلنسر: پیشنهاد "'.$req->price.'" تومان در "'. $req->delivery_date .'" روز ';
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
        $secs = $req->tempSecurePayments()->get();
        foreach ($secs as $sec) {
            SecurePayment::create([
                'title' => $sec->title,
                'price' => $sec->price,
                'status' => SecurePayment::CREATED_STATUS,
                'user_id' => $sec->user_id,
                'to_id' => $sec->to_id,
                'request_id' => $req->id,
                'project_id' => $sec->project_id,
                'is_first' => true,
            ]);
            $body = 'پرداخت امن "' . $sec->title . '" : "' . $sec->price . '" ریال "';
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
}
