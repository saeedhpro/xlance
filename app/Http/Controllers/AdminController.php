<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptOrRejectProjectRequest;
use App\Http\Requests\ChargeUserWalletRequest;
use App\Http\Resources\DisputeCollectionResource;
use App\Http\Resources\NotificationCollectionResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\PortfolioCollectionResource;
use App\Http\Resources\PortfolioResource;
use App\Http\Resources\PostCollectionResource;
use App\Http\Resources\ProjectCollectionResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\StoryCollectionResource;
use App\Http\Resources\UserCollectionResource;
use App\Http\Resources\WithdrawRequestCollectionResource;
use App\Http\Resources\WithdrawRequestResource;
use App\Models\Dispute;
use App\Models\Notification;
use App\Models\PaymentHistory;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Project;
use App\Models\Story;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Request as HttpRequest;
use MannikJ\Laravel\Wallet\Exceptions\UnacceptedTransactionException;
use MannikJ\Laravel\Wallet\Models\Wallet;

class AdminController extends Controller
{
    public function users()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            return new UserCollectionResource(User::all());
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function projects()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            return new ProjectCollectionResource(Project::all()->filter(function(Project $p){
                return $p->status != Project::IN_PAY_STATUS;
            })->sortByDesc('created_at'));
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function verifyProjects(AcceptOrRejectProjectRequest $request, Project $project)
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
           if($project->status == Project::CREATED_STATUS) {
               $project->update([
                   'verified' => $request->accepted,
                   'status' => $request->accepted ? Project::PUBLISHED_STATUS : Project::REJECTED_STATUS
               ]);
               $project->notifications()->create(array(
                   'text' => $request->accepted ? 'پروژه '. $project->title .' تایید شد' : 'پروژه '. $project->title .' رد شد',
                   'type' => Notification::PROJECT,
                   'user_id' => $auth->id,
                   'image_id' => null
               ));
               $admins = User::all()->filter(function (User $u){
                   return $u->hasRole('admin');
               })->pluck('id');
               $ids = collect($admins->values());
               $ids->push($project->employer->id);
               foreach ($ids as $id) {
                   $project->notifications()->create(array(
                       'text' => $request->accepted ? 'پروژه '. $project->title .' تایید شد' : 'پروژه '. $project->title .' رد شد',
                       'type' => Notification::PROJECT,
                       'user_id' => $id,
                       'image_id' => null
                   ));
               }
               $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
               $users = User::all()->whereIn('id', $ids->toArray());
               Notification::sendNotificationToAll($emails->toArray(), $request->accepted ? 'پروژه '. $project->title .' تایید شد' : 'پروژه '. $project->title .' رد شد', $request->accepted ? 'پروژه '. $project->title .' تایید شد' : 'پروژه '. $project->title .' رد شد', null);
               Notification::sendNotificationToUsers($users);
               return new ProjectResource($project);
           } else {
               return response()->json(['errors' => ['project' => 'تنها می توانید پروژه های تازه ایجاد شده را تایید کنید']], 422);
           }
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function posts()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            return new PostCollectionResource(Post::all());
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function stories()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            return new StoryCollectionResource(Story::all());
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function disputes()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            return new DisputeCollectionResource(Dispute::all()->sortByDesc('id'));
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function notifications()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            $notifications = $auth->notifs()->get()->sortByDesc('created_at');
            return new NotificationCollectionResource($notifications);
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function withdraws()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
//            return new WithdrawRequestCollectionResource(WithdrawRequest::all()->filter(function (WithdrawRequest $i) {
//                return $i->status == WithdrawRequest::CREATED_STATUS;
//            })->sort(function ($a, $b) {
//                return strtotime($a->created_at) < strtotime($b->created_at);
//            }));

            return new WithdrawRequestCollectionResource(WithdrawRequest::all()->sort(function ($a, $b) {
                return strtotime($a->created_at) < strtotime($b->created_at);
            }));
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function showWithdraw(WithdrawRequest $withdraw)
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            return new WithdrawRequestResource($withdraw);
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function acceptOrRejectWithdraw(AcceptOrRejectProjectRequest $request, WithdrawRequest $withdraw)
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            /** @var User $user */
            $user = $withdraw->user;
            /** @var Wallet $wallet */
            $wallet = $user->wallet;
            if(!$wallet->canWithdraw((int) $withdraw->amount)) {
                return  $this->accessDeniedResponse();
            }
            $withdraw->update([
                'status' => $request->accepted ? WithdrawRequest::PAYED_STATUS : WithdrawRequest::REJECTED_STATUS
            ]);
            /** @var PaymentHistory $history */
            $history = PaymentHistory::where('type', '=', PaymentHistory::WITHDRAW_TYPE)->where('history_id', '=', $withdraw->id)->first();
            $history->update([
                'status' => $request->accepted ? PaymentHistory::PAYED_STATUS : PaymentHistory::REJECTED_STATUS,
            ]);
            $history->save();
            if($request->accepted) {
                $wallet->forceWithdraw((int)$withdraw->amount);
                $text = 'درخواست برداشت واریز شد!';
            } else {
                $text = 'درخواست برداشت رد شد!';
            }
            $ids = collect([$user->id]);
            $admins = User::all()->filter(function (User $u){
                return $u->hasRole('admin');
            })->pluck('id');
            $ids->push($admins->values());
            foreach ($ids as $id) {
                $withdraw->notifications()->create(array(
                    'text' => $text,
                    'type' => Notification::WITHDRAW,
                    'user_id' => $id,
                    'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
                ));
            }
            $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
            $users = User::all()->whereIn('id', $ids->toArray());
            Notification::sendNotificationToAll($emails->toArray(), 'درخواست برداشت واریز شد!', 'درخواست برداشت واریز شد!', null);
            Notification::sendNotificationToUsers($users);
            return new WithdrawRequestResource($withdraw);
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function portfolios()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            return new PortfolioCollectionResource(Portfolio::where('status', '=', Portfolio::CREATED_STATUS)->get());
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function userPortfolios(User $user)
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            return new PortfolioCollectionResource($user->portfolios()->where('status', '=', Portfolio::CREATED_STATUS)->get());
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function acceptOrRejectPortfolio(AcceptOrRejectProjectRequest $request, Portfolio $portfolio)
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            $portfolio->update([
                'status' => $request->accepted ? Portfolio::ACCEPTED_STATUS : Portfolio::REJECTED_STATUS
            ]);
            return new PortfolioResource($portfolio);
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function usersChart()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            $employers = User::all()->where('created_at', '>=', Carbon::now()->subYears(2))->filter(function (User $user) {
                return $user->hasRole('employer') &&
                    (!$user->hasRole('freelancer') || !$user->hasRole('admin'));
            })->count();
            $validatedFreelancer = User::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (User $user) {
                return $user->hasRole('freelancer') && $user->isValidated();
            })->count();
            $awaitingFreelancer = User::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (User $user) {
                return $user->hasRole('freelancer') &&
                    $user->profile->sheba != null &&
                    $user->profile->avatar != null &&
                    $user->profile->national_card != null &&
                    !$user->isValidated();
            })->count();
            $unUploadedFreelancer = User::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (User $user) {
                return $user->hasRole('freelancer') && (
                    $user->profile->sheba == null ||
                    $user->profile->avatar == null ||
                    $user->profile->national_card == null ||
                    !$user->isValidated());
            })->count();

//            $data = [
//                'series' => [
//                    [
//                        'name' => 'کارفرما',
//                        'data' => [
//                            $employers,
//                            0
//                        ]
//                    ],
//                    [
//                        'name' => 'فریلنسر تایید شده',
//                        'data' => [
//                            0,
//                            $validatedFreelancer,
//                        ]
//                    ],
//                    [
//                        'name' => 'فریلنسر در انتظار تایید',
//                        'data' => [
//                            0,
//                            $awaitingFreelancer,
//                        ]
//                    ],
//                    [
//                        'name' => 'فریلنسر بدون مدارک',
//                        'data' => [
//                            0,
//                            $unUploadedFreelancer,
//                        ]
//                    ],
//                ]
//            ];
            $data = [
                'series' => [
                    [
                        'data' => [
                            $employers,
                            $validatedFreelancer,
                            $awaitingFreelancer,
                            $unUploadedFreelancer
                        ]
                    ],
                ]
            ];
            return response()->json([
                'data' => $data
            ]);
        } else {
            return  $this->accessDeniedResponse();
        }
    }
    public function projectsChart()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            $finishedProjects = Project::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (Project $project) {
                return $project->status == Project::FINISHED_STATUS;
            })->count();
            $startedProjects = Project::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (Project $project) {
                return $project->status == Project::STARTED_STATUS;
            })->count();
            $disputedProjects = Project::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (Project $project) {
                return $project->status == Project::DISPUTED_STATUS;
            })->count();

            $data = [
                'series' => [
                    [
                        'data' => [
                            $finishedProjects,
                            $startedProjects,
                            $disputedProjects
                        ]
                    ],
                ]
            ];
            return response()->json([
                'data' => $data
            ]);
        } else {
            return  $this->accessDeniedResponse();
        }
    }
    public function disputesChart()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            $openDisputes = Dispute::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (Dispute $dispute) {
                return $dispute->status == Dispute::CREATED_STATUS;
            })->count();
            $closeDisputes = Dispute::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (Dispute $dispute) {
                return $dispute->status == Dispute::CLOSED_STATUS;
            })->count();
            $inProgressDisputes = Dispute::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (Dispute $dispute) {
                return $dispute->status == Dispute::IN_PROGRESS_STATUS;
            })->count();

            $data = [
                'series' => [
                    [
                        'data' => [
                            $openDisputes,
                            $closeDisputes,
                            $inProgressDisputes
                        ]
                    ],
                ]
            ];
            return response()->json([
                'data' => $data
            ]);
        } else {
            return  $this->accessDeniedResponse();
        }
    }
    public function paymentsChart()
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            $payments = Transaction::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (Transaction $transaction) {
                return $transaction->status == Transaction::PAYED_STATUS;
            })->pluck('amount');
            $withdraws = WithdrawRequest::all()->where('created_at', '>=', Carbon::now()->subMonth())->filter(function (WithdrawRequest $withdrawRequest) {
                return $withdrawRequest->status == WithdrawRequest::PAYED_STATUS;
            })->pluck('amount');
            $data = [
                'series' => [
                    [
                        'name' => 'پرداخت ها',
                        'data' => $payments
                    ],
                    [
                        'name' => 'برداشت ها',
                        'data' => $withdraws
                    ],
                ]
            ];
            return response()->json([
                'data' => $data,
            ]);
        } else {
            return  $this->accessDeniedResponse();
        }
    }

    public function searchUser(HttpRequest $request)
    {
        $skills = $request->has('skills') ? $request->get('skills') : collect([]);
        $role = $request->get('role');
        $email = $request->get('email');
        $phone_number = $request->get('phone');
        $username = $request->get('username');
        /** @var Collection $users */
        $users = User::with('skills')->get();
        $users = $users->filter(function (User $user) {
            return $user->hasRole('freelancer') || $user->hasRole('employer');
        });
        if($role && $role !== 'both') {
            $users = $users->filter(function (User $user) use($role) {
                return $user->hasRole($role);
            });
        } else {
            if($role === 'both') {
                $users = $users->filter(function (User $user) use($role) {
                    return $user->hasRole('freelancer') || $user->hasRole('employer');
                });
            }
        }
        if($email) {
            $users = $users->filter(function (User $user) use($email){
                return $this->isLike($user->email, $email);
            });
        }
        if($username) {
            $users = $users->filter(function (User $user) use($username){
                return $this->isLike($user->username, $username);
            });
        }
        if($phone_number) {
            $users = $users->filter(function (User $user) use($phone_number){
                return $user->phone_numebr && $this->isLike($user->phone_number, $phone_number);
            });
        }
        if($skills && count($skills) > 0) {
            $users = $users->filter(function (User $user) use($skills) {
                $ids = $user->skills->pluck('id')->toArray();
                return count(array_intersect($ids, $skills)) > 0;
            });
        }
        return new UserCollectionResource($users);
    }
    public function search(Request $request)
    {
        $term = $request->get('term');
        $skills = $request->get('skills');
        $min_price = $request->get('min_price');
        $max_price = $request->get('max_price');
        $projects = Project::all()->where('status', '=', Project::PUBLISHED_STATUS)->sortByDesc('created_at')->filter(function (Project $project) {
            return Carbon::parse($project->created_at)->diffInDays(Carbon::now()) < 14;
        });
        if($term) {
            $projects = $projects->filter(function (Project $project) use($term){
                return $this->isLike($project->title, $term);
            });
        }
        if($min_price) {
            $projects = $projects->filter(function (Project $project) use($min_price){
                return $project->min_price >= $min_price;
            });
        }
        if($max_price) {
            $projects = $projects->filter(function (Project $project) use($max_price){
                return $project->max_price <= $max_price;
            });
        }
        if($skills && count($skills) > 0) {
            $projects = $projects->filter(function (Project $project) use($skills) {
                $ids = $project->skills->pluck('id')->toArray();
                return count(array_intersect($ids, $skills)) > 0;
            });
        }
        return new ProjectCollectionResource($projects);
    }

    public function chargeUserWallet(ChargeUserWalletRequest $request)
    {
        /** @var User $auth */
        $auth = $this->getAuth();
        if($auth->hasRole('admin')) {
            /** @var User $user */
            $user = User::with('wallet')->findOrFail($request->get('user_id'));
            /** @var Wallet $wallet */
            $wallet = $user->wallet;
            try {
                $amount = (int)$request->get('amount') / 10;
                $wallet->deposit($amount);
                $body = 'حساب شما به مبلغ '. $amount .' تومان شارژ شد';
                $user->notifs()->create(array(
                    'text' => $body,
                    'type' => Notification::EMPLOYER,
                    'user_id' => $user->id,
                    'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
                ));
                $admins = User::all()->filter(function (User $u){
                    return $u->hasRole('admin');
                })->pluck('id');
                $ids = collect($admins->values());
                $notificationBody =  'حساب کاربر '. $user->username .' به مبلغ '. $amount .' تومان شارژ شد';
                foreach ($ids as $id) {
                    $user->notifs()->create(array(
                        'text' => $notificationBody,
                        'type' => Notification::EMPLOYER,
                        'user_id' => $id,
                        'image_id' => $user->profile->avatar ? $user->profile->avatar->id : null
                    ));
                }
                $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
                $users = User::all()->whereIn('id', $ids->toArray());
                Notification::sendNotificationToAll($emails->toArray(), $notificationBody, $notificationBody, null);
                $emails = User::all()->whereIn('id', [$user->id])->pluck('email');
                Notification::sendNotificationToAll($emails->toArray(), $body, $body, null);
                Notification::sendNotificationToUsers($users);
                Notification::sendNotificationToUsers(collect([$user]));
                return true;
            } catch (UnacceptedTransactionException $e) {
                return false;
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }
}
