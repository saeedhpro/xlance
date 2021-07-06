<?php

namespace App\Http\Controllers;

use App\Events\NewConversationEvent;
use App\Models\Conversation;
use App\Models\PaymentHistory;
use App\Models\Project;
use App\Models\RequestPackage;
use App\Models\SecurePayment;
use App\Models\SelectedPlan;
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
                $status = 200;
                $message = 'پرداخت با موفقیت انجام شد برای ادامه روی دکمه ی بازگشت کلیک کنید';
                $url = 'https://xlance.ir/projects/'.$project->id.'?referenceId='.$referenceId;
                return view('payment', compact('referenceId', 'status', 'message', 'url'));
            } else if($t->type == Transaction::PACKAGE_TYPE) {
                /** @var User $user */
                $user = $t->user;
                $monthly = $t->is_monthly;
                $package = $t->package;
                $lastPlan = SelectedPlan::where('user_id', '=', $user->id)
                    ->last();
                $user->selectedPlans()->create([
                    'start_date' => Carbon::createFromTimestamp($lastPlan->end_date)->addDay(),
                    'end_date' => $monthly ? Carbon::createFromTimestamp($lastPlan->end_date)->addDay()->addMonth() : Carbon::createFromTimestamp($lastPlan->end_date)->addDay()->addYear(),
                    'number' => $package->number,
                ]);
                /** @var Wallet $wallet */
                $wallet = $user->wallet;
                $wallet->forceWithdraw((int) $t->price);
                $status = 200;
                $t->update([
                    'status' => Transaction::PAYED_STATUS
                ]);
                $message = 'پرداخت با موفقیت انجام شد برای ادامه روی دکمه ی بازگشت کلیک کنید';
                $url = 'https://xlance.ir/membership-upgrade/?referenceId='.$referenceId.'&id='.$package->id;
                return view('payment', compact('referenceId', 'status', 'message', 'url'));
            } else if($t->type == Transaction::SECURE_PAYMENT_TYPE) {
                /** @var SecurePayment $securePayment */
                $securePayment = $t->securePayment;
                $securePayment->update([
                   'status' => SecurePayment::PAYED_STATUS
                ]);
                $project = $t->project;
                $status = 200;
                $t->update([
                    'status' => Transaction::PAYED_STATUS
                ]);
                $message = 'پرداخت با موفقیت انجام شد برای ادامه روی دکمه ی بازگشت کلیک کنید';
                $url = 'https://xlance.ir/projects/'. $project->id .'?referenceId='.$referenceId.'&id='.$securePayment->id;
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
}
