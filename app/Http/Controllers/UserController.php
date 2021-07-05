<?php

namespace App\Http\Controllers;

use App\Events\NewConversationEvent;
use App\Events\NewMessageEvent;
use App\Events\NewMessageNotificationEvent;
use App\Http\Requests\AcceptOrRejectProjectRequest;
use App\Http\Requests\AddUserSkillRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\FollowRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RemoveUserSkillRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SeenConversationRequest;
use App\Http\Requests\SeenMessageRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\SendRequestForProjectRequest;
use App\Http\Requests\SendResetLinkEmailRequest;
use App\Http\Requests\SetPackageRequest;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateShebaRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\AcceptFreelancerResource;
use App\Http\Resources\AssetResource;
use App\Http\Resources\ConversationCollectionResource;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\DisputeCollectionResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\NotificationCollectionResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\PaymentHistoryCollectionResource;
use App\Http\Resources\PortfolioCollectionResource;
use App\Http\Resources\PostCollectionResource;
use App\Http\Resources\ProjectCollectionResource;
use App\Http\Resources\RequestCollectionResource;
use App\Http\Resources\RequestResource;
use App\Http\Resources\SecurePaymentCollectionResource;
use App\Http\Resources\SkillCollectionResource;
use App\Http\Resources\StoryCollectionResource;
use App\Http\Resources\UserCollectionResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WithdrawRequestCollectionResource;
use App\Http\Resources\WithdrawRequestResource;
use App\Interfaces\UserInterface;
use App\Models\AcceptFreelancerRequest;
use App\Models\Conversation;
use App\Models\Dispute;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Request;
use App\Models\RequestPackage;
use App\Models\ResetPassword;
use App\Models\SecurePayment;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\Transaction;
use App\Models\Upload;
use App\Models\User;
use App\Models\WithdrawRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Hash;
use MannikJ\Laravel\Wallet\Models\Wallet;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class UserController extends Controller
{
    protected $userRepository;
    public function __construct(UserInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(RegisterRequest $request)
    {
        /** @var User $user */
        $user = $this->userRepository->register($request);
        $token = $user->createToken('xlance')->accessToken;
        return \response()->json(['token' => $token, 'user' => new UserResource($user)], 201);
    }

    public function login(LoginRequest $request)
    {
        /** @var User $user */
        $user = $this->userRepository->login($request);
        if(!$user){
            return response()->json(['errors' => ['user' => 'کاربر پیدا نشد']], 404);
        } else {
            if($user->email_verified_at == null) {
                return response()->json(['errors' => ['email' => ['ایمیل تایید نشده است']]], 422);
            } else if($this->checkPassword($request->password, $user->password)){
                $token = $user->createToken('xlance')->accessToken;
                return response()->json(['token' => $token, 'user' => new UserResource($user)], 200);
            } else {
                return response()->json(['errors' => ['password' => 'پسورد صحیح نیست']], 422);
            }
        }
    }

    private function checkPassword($password, $userPassword)
    {
        return Hash::check($password, $userPassword);
    }


    public function sendResetLinkEmail(SendResetLinkEmailRequest $request)
    {
        /** @var User $user */
        $user = User::whereEmail($request->email)->first();
        $token = $user->genResetToken();
        /** @var ResetPassword $passwordReset */
        $passwordReset = ResetPassword::whereEmail($request->email)->first();
        if($passwordReset) {
            $passwordReset->update([
                'token' => $token,
                'expires_at' => Carbon::now()->addMinutes(60),
            ]);
        } else {
            ResetPassword::create([
                'email' => $request->get('email'),
                'token' => $token,
                'expires_at' => Carbon::now()->addMinutes(60),
            ]);
        }

        $user->sendPasswordResetNotification($token);
        return response()->json(['message' => 'لینک ارسال شد'], 200);
    }

    public function checkToken(ResetPasswordRequest $request)
    {
        $token = $request->get('token');
        /** @var ResetPassword $passwordReset */
        $passwordReset = ResetPassword::whereEmail($request->get('email'))->first();
        if(!$passwordReset) {
            return response()->json([
                'errors' => [
                    'email' => [
                        'ایمیل معتبر نیست'
                    ]
                ],
            ], 404);
        }
        if($token !== $passwordReset->token) {
            return response()->json([
                'errors' => [
                    'token' => [
                        'توکن معتبر نیست'
                    ]
                ],
            ], 404);
        }
        if(!Carbon::now()->greaterThan($passwordReset->expires_at)) {
            return response()->json([
                'errors' => [
                    'token' => [
                        'توکن منقضی شده است'
                    ]
                ],
            ], 500);
        }
        $passwordReset->forceDelete();
        return response()->json(['data' => 'توکن معتبر است'], 200);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        /** @var User $user */
        $user = User::whereEmail($request->email)->first();
        if($user) {
            $user->update(['password' => bcrypt($request->password)]);
            return response()->json(['data' => 'پسورد با موفقیت تغییر کرد'], 200);
        } else {
            return response()->json(['data' => 'کاربر یافت نشد'], 404);
        }
    }

    public function changeAuthPassword(UpdatePasswordRequest $request){
        /** @var User $auth */
        $auth = auth()->user();
        if($this->checkPassword($request->password, $auth->password) || $auth->hasRole('admin')) {
            return response()->json(['data' => $this->userRepository->changeAuthPassword($request)]);
        } else {
            return response()->json(['errors' => ['password' => ['رمز عبور فعلی صحیح نیست!']]], 422);
        }
    }

    public function all() {
        return new UserCollectionResource($this->userRepository->all());
    }

    public function freelancers(): UserCollectionResource
    {
        $freelancers = $this->userRepository->freelancers();
        if($this->hasPage()) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            return new UserCollectionResource($this->paginateCollection($freelancers, $limit, 'page'));
        }
        return new UserCollectionResource($freelancers);
    }

    public function employers(): UserCollectionResource
    {
        $employers = $this->userRepository->employers();
        if ($this->hasPage()) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            return new UserCollectionResource($this->paginateCollection($employers, $limit, 'page'));
        }
        return new UserCollectionResource($employers);
    }

    public function show($id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        return new UserResource($user);
    }

    public function updateMe(UpdateUserRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $auth = $this->userRepository->updateMe($request, $auth);
        return new UserResource($auth);
    }

    public function ownPosts()
    {
        /** @var User $user */
        $user = auth()->user();
        $posts = $user->posts()->get();
        return new PostCollectionResource($posts);
    }

    public function ownStories()
    {
        /** @var User $user */
        $user = auth()->user();
        $stories = $user->stories()->where('created_at', '>=', Carbon::now()->subDay())->get();
        return new StoryCollectionResource($stories);
    }

    public function posts($id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        $posts = $user->posts()->get();
        return new PostCollectionResource($posts);
    }

    public function stories($id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        $stories = $user->stories()->where('created_at', '<=', Carbon::now()->subDay())->get();
        return new StoryCollectionResource($stories);
    }

    public function me()
    {
        return new UserResource(auth()->user());
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $user = $this->userRepository->updateProfile($request, $user);
        return new UserResource($user);
    }

    public function updateAvatar(UpdateAvatarRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $avatar = $this->userRepository->updateAvatar($request, $user->profile);
        return new AssetResource($avatar);
    }

    public function acceptOrRejectAvatar(AcceptOrRejectProjectRequest $request, $id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        $updated = $this->userRepository->acceptOrRejectAvatar($request, $user->profile);
        return response()->json(['message' => $updated ? 'تایید شد' : 'تایید نشد'], 200);
    }

    public function acceptOrRejectBackground(AcceptOrRejectProjectRequest $request, $id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        $updated = $this->userRepository->acceptOrRejectBackground($request, $user->profile);
        return response()->json(['message' => $updated ? 'تایید شد' : 'تایید نشد'], 200);
    }

    public function acceptOrRejectNationalCard(AcceptOrRejectProjectRequest $request, $id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        $updated = $this->userRepository->acceptOrRejectNationalCard($request, $user->profile);
        return response()->json(['message' => $updated ? 'تایید شد' : 'تایید نشد'], 200);
    }

    public function acceptOrRejectSheba(AcceptOrRejectProjectRequest $request, $id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        $updated = $this->userRepository->acceptOrRejectSheba($request, $user->profile);
        return response()->json(['message' => $updated ? 'تایید شد' : 'تایید نشد'], 200);
    }

    public function updateBackground(UpdateAvatarRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $background = $this->userRepository->updateBackground($request, $user->profile);
        return new AssetResource($background);
    }

    public function updateNationalCard(UpdateAvatarRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $nationalCard = $this->userRepository->updateNationalCard($request, $user->profile);
        return new AssetResource($nationalCard);
    }

    public function updateSheba(UpdateShebaRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $updated = $this->userRepository->updateSheba($request, $user->profile);
        return response()->json(['message' => $updated ? 'آپدیت شد' : 'متاسفانه خطایی رخ داده است'], $updated ? 200 : 500);
    }

    public function skills()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return new SkillCollectionResource($auth->skills()->get());
    }

    public function addSkill(AddUserSkillRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $skills = Skill::findOrFail($request->get('skills'));
        $auth->skills()->detach();
        $auth->skills()->attach($skills);
        return new SkillCollectionResource($auth->skills()->get());
    }

    public function removeSkill(RemoveUserSkillRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $skill = Skill::findOrFail($request->skill_id);
        $auth->skills()->detach($skill->id);
        return new SkillCollectionResource($auth->skills()->get());
    }

    public function authCanDoProjects()
    {
        $projects = $this->userRepository->authCanDoProjects();
        return response()->json([
            'count' => count($projects)
        ]);
    }

    public function authProjects()
    {
        $auth = auth()->user();
        return $this->userProjects($auth->id);
    }

    public function authAllProjects()
    {
        $auth = auth()->user();
        return $this->userAllProjects($auth->id);
    }

    public function userProjects($id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        return [
            'finished' => new ProjectCollectionResource(
                $user->ownFinishedProjects()->sortByDesc('created_at')),
            'started' => new ProjectCollectionResource(
                $user->ownInProgressProjects()->sortByDesc('created_at')),
            'published' => new ProjectCollectionResource(
                $user->publishedProjects()->get()->sortByDesc('created_at')),
            'created' => new ProjectCollectionResource(
                $user->ownOnlyCreatedProjects()->sortByDesc('created_at')),
        ];
    }

    public function userAllProjects($id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        return new ProjectCollectionResource($user->allProjects()->sortByDesc('created_at'));
    }

    public function follow(FollowRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $user = $this->userRepository->findOneOrFail($request->get('user_id'));
        if($auth->can('follow-user', $user)) {
            if(!$user->isFollowedBy($auth)) {
                $auth->follow($user);
            }
            return response()->json(['success' => 'با موفقیت انجام شد!'], 200);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function unFollow(FollowRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $user = $this->userRepository->findOneOrFail($request->get('user_id'));
        if($auth->can('unFollow-user', $user)) {
            if($user->isFollowedBy($auth)) {
                $auth->unfollow($user);
            }
            return response()->json(['success' => 'با موفقیت انجام شد!'], 200);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function authFollowers()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return new UserCollectionResource($this->userRepository->userFollowers($auth->id));
    }

    public function authFollowings()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return new UserCollectionResource($this->userRepository->userFollowings($auth->id));
    }

    public function userFollowers($id)
    {
        return new UserCollectionResource($this->userRepository->userFollowers($id));
    }

    public function userFollowings($id)
    {
        return new UserCollectionResource($this->userRepository->userFollowers($id));
    }

    public function authReceivedRequests()
    {
        $auth = auth()->user();
        return $this->userReceivedRequests($auth->id);
    }

    public function userReceivedRequests($id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        return [
            'finished' => new RequestCollectionResource(
                $user->receivedRequests()->where('status', '=', Request::FINISHED_STATUS)->get()),
            'created' => new RequestCollectionResource(
                $user->receivedRequests()->where('status', '=', Request::CREATED_STATUS)->get()),
            'accepted' => new RequestCollectionResource(
                $user->receivedRequests()->where('status', '=', Request::ACCEPTED_STATUS)->get()),
        ];
    }

    public function authSentRequests()
    {
        $auth = auth()->user();
        return $this->userSentRequests($auth->id);
    }

    public function userSentRequests($id)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($id);
        $own = Request::with('project')
            ->where('user_id', '=', $id)
            ->where('status', '!=', Request::REJECTED_STATUS)
            ->get()->sortByDesc('created_at');
//        $created = Request::with('project')->where('user_id', '=', $id)->get()->filter(function (Request $request) {
//           return $request->status == Request::CREATED_STATUS || $request->status == Request::ACCEPTED_STATUS;
//        });
//        $created = $created->filter(function(Request $r) {
//            return $r->user->id != $r->project->employer->id;
//        })->sortByDesc('created_at');
//        $accepted = $user->sentRequests()->where('status', '=', Request::STARTED_STATUS)->get()->sortByDesc('created_at');
        $created = $own->filter(function (Request $request) {
            return $request->project->status == Project::PUBLISHED_STATUS;
        });
        $accepted = $own->filter(function (Request $request) {
            return $request->project->status == Project::STARTED_STATUS || $request->project->status == Project::DISPUTED_STATUS;
        });
        $finished = $own->filter(function (Request $request) {
            return $request->project->status == Project::FINISHED_STATUS ||
                $request->project->status == Project::CANCELED_STATUS ;
        });
        return [
            'finished' => new RequestCollectionResource($finished),
            'created' => new RequestCollectionResource($created),
            'accepted' => new RequestCollectionResource($accepted),
        ];

    }

    public function sendRequest(SendRequestForProjectRequest $sendRequestForProjectRequest)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Project $project */
        $project = Project::findOrFail($sendRequestForProjectRequest->project_id);
        $project_ids = $auth->sentRequests()->pluck('project_id')->toArray();
        $c = true;
        if(in_array($project->id, $project_ids)){
            $c = false;
        }
        if(!$c) {
            return response()->json(['errors' => ['project' => ['شما قبلا برای این پروژه درخواست ثبت کرده اید!']]], 422);
        }
        if($auth->number == 0) {
            return response()->json(['errors' => ['project' => ['تعداد درخواست های شما تمام شده است. برای ارسال درخواست پلن جدید تهیه کنید!']]], 422);
        }
        if($auth->id === $project->employer->id) {
            return response()->json(['errors' => ['project' => ['شما کارفرمای این پروژه هستید پس نمی توانید برای این پروژه درخواست ثبت کنید!']]], 422);
        }
        if($project->freelancer !== null) {
            return response()->json(['errors' => ['project' => ['پروژه درحال انجا می باشد!']]], 422);
        }
        if(!$auth->isValidated()) {
            return response()->json(['errors' => ['project' => ['مدارک شما تکمیل و یا تایید نشده است!']]], 422);
        }
        if($auth->hasRole('admin')) {
            return response()->json(['errors' => ['project' => ['کاربر ادمین نمی تواند درخواست ثبت کند!']]], 422);
        }
        /** @var Wallet $wallet */
        $wallet = $auth->wallet;
        /** @var Setting $settings */
        $settings = Setting::all()->first();
        $amount = (int) $settings->project_price;
        if($sendRequestForProjectRequest->is_distinguished) {
            $amount = $amount + (int) $settings->distinguished_price;
        }
        if($sendRequestForProjectRequest->is_distinguished) {
            $amount = $amount + (int) $settings->sponsored_price;
        }
        if((int) $wallet->balance < $amount) {
            return response()->json(['errors' => ['amount' =>['موجودی کیف پول شما کمتر از مبلغ مورد نیاز می باشد!']]], 422);
        }
        $spPrices = $this->getSecurePayments($sendRequestForProjectRequest);
        if((int) $sendRequestForProjectRequest->price != $spPrices) {
            return response()->json(['errors' => ['amount' =>['مبلغ اعلام شده با مجموع مبلغ پرداخت های امن متفاوت است!']]], 422);
        }
        $request = $this->userRepository->sendRequest($sendRequestForProjectRequest);
        return new RequestResource($request);
    }

    public function getSecurePayments(SendRequestForProjectRequest $request){
        $securePayments = $request->get('new_secure_payments');
        if(!is_null($securePayments)) {
            if(is_array($securePayments)) {
                $price = 0;
                foreach ($securePayments as $s) {
                    $price += (int) $s['price'];
                }
                return $price;
            } else {
                return response()->json(['errors' => ['project' => ['درخواست پرداخت صحیح نیست!']]], 422);
            }
        } else {
            return 0;
        }
    }

    public function getReceivedRequest($id)
    {
        $request = $this->userRepository->getReceivedRequest($id);
        return new RequestResource($request);
    }

//    public function acceptOrRejectReceivedRequest(AcceptOrRejectProjectRequest $projectRequest, $id)
//    {
//        /** @var User $auth */
//        $auth = auth()->user();
//        /** @var ProjectRequest $request */
//        $request = $auth->receivedRequests()->findOrFail($id);
//        if($auth->can('accept-or-reject-received-request', $request)) {
//            $request = $this->userRepository->acceptOrRejectReceivedRequest($projectRequest, $request);
//            return new RequestResource($request);
//        } else {
//            return $this->accessDeniedResponse();
//        }
//    }

    public function authProjectRequests($id)
    {
        $requests = $this->userRepository->authProjectRequests($id);
        return new RequestCollectionResource($requests->sortByDesc('created_at'));
    }

    public function authAcceptOrRejectProjectRequest(AcceptOrRejectProjectRequest $projectRequest, $id, Request $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->can('accept-or-reject-request-for-project', $request)) {
            $project = $request->project;
            $request = $this->userRepository->authAcceptOrRejectProjectRequest($projectRequest, $project, $request);
            return new RequestResource($request);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function freelancerGetAcceptProjectRequest(Project $project, AcceptFreelancerRequest $accept)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->can('freelancer-get-accept-reject-request', $accept)) {
            return new AcceptFreelancerResource($accept);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function freelancerAcceptOrRejectRequest(AcceptOrRejectProjectRequest $projectRequest, Project $project, $id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var AcceptFreelancerRequest $request */
        $request = AcceptFreelancerRequest::findOrFail($id);
        $count = $project->acceptFreelancerRequest()
            ->where('freelancer_id', '=', $auth->id)
            ->where('status', '=', AcceptFreelancerRequest::CREATED_STATUS)->count();
        if($auth->id != $request->freelancer->id) {
            return response()->json(['errors' => ['freelancer' => ['شما نمی توانید این درخواست را تایید کنید!']]], 422);
        } else if($project->selected_request_id != null) {
            return response()->json(['errors' => ['project' => ['برای این پروژه درخواست دیگری تایید شده است!']]], 422);
        } else if($count != 1){
            return response()->json(['errors' => ['count' => ['شما در خواست تایید شده برای این پروژه ندارید!']]], 422);
        }
        $request = $this->userRepository->freelancerAcceptOrRejectRequest($projectRequest, $project, $request);
        return new AcceptFreelancerResource($request);
//        if($auth->can('freelancer-accept-or-reject-request', $request)) {
//            $request = $this->userRepository->freelancerAcceptOrRejectRequest($projectRequest, $project, $request);
//            return new AcceptFreelancerResource($request);
//        } else {
//            return $this->accessDeniedResponse();
//        }
    }

    public function deposit(DepositRequest $request)
    {
        return $this->userRepository->deposit($request->amount);
    }

    public function withdraw(DepositRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        if($user->profile->sheba == null) {
            return response()->json(['errors' => ['sheba' => ['ابتدا شماره شبا را تکمیل کنید!']]], 422);
        }
        if(!$user->profile->sheba_accepted) {
            return response()->json(['errors' => ['sheba' => ['شماره شبا تایید نشده است!']]], 422);
        }
        /** @var Wallet $wallet */
        $wallet = $user->wallet;
        if($wallet->balance < (int) $request->amount) {
            return response()->json(['errors' => ['sheba' => ['موجودی کیف پول شما کمتر از مبلغ مورد نیاز می باشد!']]], 422);
        }
        if($this->userRepository->withdraw($request->amount)) {
            return response()->json(['success' => true, 'message' => 'درخواست برداشت با موفقیت ثبت شد'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'متاسفانه خطایی رخ داده است.'], 500);
        }
    }

    public function withdraws()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return new WithdrawRequestCollectionResource($auth->withdraws);
    }

    public function showWithdraw(WithdrawRequest $withdraw)
    {
        return new WithdrawRequestResource($withdraw);
    }

    public function wallet()
    {
        $wallet = $this->userRepository->wallet();
        return new WalletResource($wallet);
    }

    public function ownLikedPosts()
    {
        $posts = $this->userRepository->ownLikedPosts();
        return new PostCollectionResource($posts);
    }

    public function userLikedPosts($id)
    {
        $posts = $this->userRepository->userLikedPosts($id);
        return new PostCollectionResource($posts);
    }

    public function ownSavedPosts()
    {
        $posts = $this->userRepository->ownSavedPosts();
        return new PostCollectionResource($posts);
    }

    public function userSavedPosts($id)
    {
        $posts = $this->userRepository->userSavedPosts($id);
        return new PostCollectionResource($posts);
    }

    public function ownBookmarkedPosts()
    {
        $posts = $this->userRepository->ownBookmarkedPosts();
        return new PostCollectionResource($posts);
    }

    public function ownFollowingsPosts()
    {
        $posts = $this->userRepository->ownFollowingsPosts();
        return new PostCollectionResource($posts);
    }

    public function userBookmarkedPosts($id)
    {
        $posts = $this->userRepository->userBookmarkedPosts($id);
        return new PostCollectionResource($posts);
    }

    public function authPortfolios()
    {
        return new PortfolioCollectionResource($this->userRepository->authPortfolios());
    }

    public function userPortfolios($id)
    {
        return new PortfolioCollectionResource($this->userRepository->userPortfolios($id));
    }

    public function blocked()
    {
        return new UserCollectionResource($this->userRepository->blockedUsers());
    }

    public function blockAndUnblockUser($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $user = $this->userRepository->findOneOrFail($id);
        if($auth->can('block-user', $user)) {
            $status = $this->userRepository->blockAndUnblockUser($id);
            return response()->json(['blocked' => $status]);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function disputes()
    {
        /** @var Collection $disputes */
        $disputes = $this->userRepository->disputes();
        return [
            'open' => new DisputeCollectionResource($disputes->filter(function($i) {
                return $i->status !== Dispute::CLOSED_STATUS;
            })->sortByDesc('id')),
            'close' => new DisputeCollectionResource($disputes->filter(function($i) {
                return $i->status === Dispute::CLOSED_STATUS;
            })->sortByDesc('id'))
        ];
    }

    public function notifications()
    {
        if (\request()->get('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $notifications = $this->paginateCollection($this->userRepository->authNotifications(), $limit, 'page');
        } else {
            $notifications = $this->userRepository->authNotifications();
        }
        return new NotificationCollectionResource($notifications);
    }

    public function seenNotifications()
    {
        return $this->userRepository->seenNotifications();
    }

    public function adminBlockUser($id)
    {
        $user = $this->userRepository->findOneOrFail($id);
        $user->update([
            'blocked' => $user->blocked ? false : true,
        ]);
        return new UserResource($user);
    }

    public function setPackage(SetPackageRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $balance = $user->wallet->balance;
        /** @var RequestPackage $package */
        $package = RequestPackage::find($request->get('package_id'));
        $monthly = $request->get('monthly');
        $yearlyPrice = 12 * (int) $package->price - ((12 * (int) $package->price) * 20 / 100) ;
        $monthlyPrice = (int) $package->price;
        if(($monthly && $package->price > $balance ) || (!$monthly && $yearlyPrice > $balance)) {
            $amount = $monthly ? ((int) $package->price) - $balance : ((int) $package->price * 12) - $balance;
            $invoice = new Invoice;
            $invoice->amount((int) ($amount / 10));
            $invoice->detail('t_id', $invoice->getTransactionId());
            return Payment::purchase($invoice, function($driver, $transactionId) use($user, $invoice, $monthly, $package){
                Transaction::create([
                    'user_id' =>  $user->id,
                    'transaction_id' => $transactionId,
                    'project_id' => null,
                    'request_package_id' => $package->id,
                    'type' => Transaction::PACKAGE_TYPE,
                    'status' => Transaction::CREATED_STATUS,
                    'is_monthly' => $monthly ? true : false,
                    'amount' => $invoice->getAmount(),
                ]);
            })->pay()->toJson();
        } else {
            if($user->request_package_id != null && $user->number == 0) {
                $user->update([
                    'requests_count' => 0,
                ]);
            }
            $user->update([
                'request_package_id' => $package->id,
                'package_expire_date' => $monthly ? Carbon::now()->addMonth() : Carbon::now()->addYear(),
                'number' => ($user->number - $user->requests_count) + $package->number,
            ]);
            /** @var Wallet $wallet */
            $wallet = $user->wallet;
            $wallet->forceWithdraw($monthly ? $monthlyPrice : $yearlyPrice);
            return response()->json(['data' => 'ارقای عضویت انجام شد'], 200);
        }
    }

    public function search(HttpRequest $request)
    {
        $term = $request->get('term');
        $limit = $this->getLimit();
        $page = $this->getPage();
        $skills = $request->get('skills');
        $users = User::all()->filter(function (User $u) {
            return $u->hasRole('freelancer');
        });
       if($term) {
           $users = $users->filter(function (User $user) use($term){
               return $this->isLike($user->username, $term) || $user->first_name && $this->isLike($user->first_name, $term) || $user->last_name && $this->isLike($user->last_name, $term);
           });
       }
        if($skills && count($skills) > 0) {
            $users = $users->filter(function (User $user) use($skills) {
                $ids = $user->skills->pluck('id')->toArray();
                return count(array_intersect($ids, $skills)) > 0;
            });
        }
        $users = $users->sort(function (User $first, User $second) {
            if($first->calcRates() == $second->calcRates()) {
                return 0;
            }
            return $first->calcRates() > $second->calcRates() ? -1 : 1;
        });
        return new UserCollectionResource($this->paginateCollection($users, $limit));
    }

    public function conversations()
    {
        /** @var User $auth */
        $auth = auth()->user();
        $conversations = Conversation::where('user_id', '=', $auth->id)->orWhere('to_id', '=', $auth->id)->get();
        return new ConversationCollectionResource($conversations->sortByDesc('created_at'));
    }

    public function sendMessage(SendMessageRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var User $user */
        $user = $this->userRepository->findOneOrFail($request->get('to_id'));
        if(!$request->get('conversation_id') && $auth->can('create-direct-conversation', $request->get('to_id'))){
            /** @var Conversation $conversation */
            $conversation = $auth->conversations()->create([
                'user_id' => $auth->id,
                'to_id' => $user->id,
                'status' => Conversation::OPEN_STATUS,
                'type' => Conversation::DIRECT_TYPE,
            ]);
            broadcast(new NewConversationEvent($auth, $conversation));
            broadcast(new NewConversationEvent($user, $conversation));
        } else {
            /** @var Conversation $conversation */
            $conversation = Conversation::findOrFail($request->get('conversation_id'));
        }
        if($conversation != null && $conversation->isDisabled()) {
            return response()->json(['errors' => ['message' => ['تا زمان ارسال پیام توسط کارفرما این چت برای شما بسته است!']]], 422);
        }
        /** @var Message $message */
        $message = $conversation->messages()->create([
            'user_id' => $auth->id,
            'to_id' => $user->id,
            'type' => $request->get('type'),
            'body' => $request->get('body'),
        ]);
        $conversation->save();
        if($request->get('type') == Message::FILE_TYPE && $request->has('upload_id')) {
            $upload = Upload::find($request->get('upload_id'));
            if($upload) {
                $message->update([
                    'upload_id' => $upload->id
                ]);
            }
        }
        $message->save();
        broadcast(new NewMessageEvent($message, $user));
        broadcast(new NewMessageEvent($message, $auth));
        $nonce = $this->getNonce();
        broadcast(new NewMessageNotificationEvent($user, $user->newMessagesCount(), $nonce));
        return new MessageResource($message);
    }

    public function user(HttpRequest $request)
    {
        $limit = $this->getLimit();
        $page = $this->getPage();
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
        return new UserCollectionResource($this->paginateCollection($users, $limit));
    }

    public function securePaymentsToOther()
    {
        /** @var User $auth */
        $auth = auth()->user();
        $payments = SecurePayment::where('to_id', '=', $auth->id)->get();
        return new SecurePaymentCollectionResource($payments);
    }

    public function securePaymentsToMe()
    {
        /** @var User $auth */
        $auth = auth()->user();
        $payments = SecurePayment::where('user_id', '=', $auth->id)->get();
        return new SecurePaymentCollectionResource($payments);
    }

    public function verifyEmail($id, $hash): JsonResponse
    {
        /** @var User $user */
        $user = User::find($id);
        if($user) {
            $trueHash = ! hash_equals((string) $hash, sha1($user->getEmailForVerification()));
            if($trueHash) {
                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                    event(new Verified($user));
                    return response()->json(['data' => 'ایمیل تایید شد'], 200);
                } else {
                    return response()->json(['data' => 'ایمیل تایید شده'], 500);
                }
            } else {
                return response()->json(['data' => 'کد منقضی شده است'], 403);
            }
        } else {
            return response()->json(['data' => 'کاربر پیدا نشد'], 404);
        }
    }

    public function paymentHistories()
    {
        $histories = $this->userRepository->paymentHistories()->sort(function ($a, $b) {
            return strtotime($a->created_at) < strtotime($b->created_at);
        });
        return new PaymentHistoryCollectionResource($histories);
    }

    public function monthlyIncome()
    {
        return $this->userRepository->monthlyIncome();
    }

    public function ownCreatedSecurePayments()
    {
        return new SecurePaymentCollectionResource($this->userRepository->ownCreatedSecurePayments());
    }

    public function ownAcceptedSecurePayments()
    {
        return new SecurePaymentCollectionResource($this->userRepository->ownAcceptedSecurePayments());
    }

    public function ownPayedSecurePayments()
    {
        return $this->userRepository->ownPayedSecurePayments();
    }

    public function ownFreeSecurePayments()
    {
        return new SecurePaymentCollectionResource($this->userRepository->ownFreeSecurePayments());
    }

    public function showNotification(Notification $notification)
    {
        return new NotificationResource($notification);
    }

    public function seenMessage(SeenMessageRequest $seenMessageRequest)
    {
        return $this->userRepository->seenMessage($seenMessageRequest);
    }

    public function seenConversation(SeenConversationRequest $seenConversationRequest)
    {
        return $this->userRepository->seenConversation($seenConversationRequest);
    }

    public function authNewMessagesCount()
    {
        return $this->userRepository->authNewMessagesCount();
    }
}
