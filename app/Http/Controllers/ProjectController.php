<?php

namespace App\Http\Controllers;

use App\Events\NewConversationEvent;
use App\Http\Requests\AcceptOrRejectChangePrice;
use App\Http\Requests\AcceptOrRejectProjectRequest;
use App\Http\Requests\AddProjectAttachmentRequest;
use App\Http\Requests\CancelProjectRequest;
use App\Http\Requests\ChangeProjectRequest;
use App\Http\Requests\RateFreelancerRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\StoreSecurePaymentRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\AssetResource;
use App\Http\Resources\CancelProjectRequestResource;
use App\Http\Resources\ChangePriceRequestCollectionResource;
use App\Http\Resources\ChangePriceResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\ProjectCollectionResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectSearchCollectionResource;
use App\Http\Resources\RequestCollectionResource;
use App\Http\Resources\SecurePaymentCollectionResource;
use App\Http\Resources\SecurePaymentResource;
use App\Interfaces\ProjectInterface;
use App\Models\ChangeProjectRequest as ChangePrice;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectProperty;
use App\Models\SecurePayment;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Upload;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Models\Request as ProjectRequest;
use MannikJ\Laravel\Wallet\Models\Wallet;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class ProjectController extends Controller
{
    protected $projectRepository;
    public function __construct(ProjectInterface $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    public function index()
    {
        if($this->hasPage()){
            $page = $this->getPage();
            return new ProjectCollectionResource($this->projectRepository->allByPagination('*','desc', 'created_at', $page));
        } else {
            return new ProjectCollectionResource($this->projectRepository->all());
        }
    }

    public function created()
    {
        $created = $this->projectRepository->created();
        return new ProjectCollectionResource($created);
    }

    public function started()
    {
        $started = $this->projectRepository->started();
        return new ProjectCollectionResource($started);
    }

    public function finished()
    {
        $finished = $this->projectRepository->finished();
        return new ProjectCollectionResource($finished);
    }

    public function store(StoreProjectRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();
        $balance = $user->wallet->balance;
        /** @var Setting $setting */
        $setting = Setting::all()->first();
        $priceForProject = $setting->project_price;
        $price = $this->calcProjectPrice($request, $priceForProject);
        $needPay = $price > $balance;
        $request['employer_id'] = $user->id;
        $request['status'] = $needPay ? Project::IN_PAY_STATUS : Project::CREATED_STATUS;
        /** @var Project $project */
        $project = $this->projectRepository->create($request->only([
            'title',
            'status',
            'type',
            'description',
            'min_price',
            'max_price',
            'employer_id',
        ]));
        $props = $request->get('new_properties');
        $skills = $request->get('new_skills');
        $project->properties()->sync([]);
        $project->skills()->sync([]);
        $project->properties()->sync($props);
        $project->skills()->sync($skills);
        $project = $this->syncAttachments($request, $project);
        if($request->get('freelancer_id')) {
            $freelancer_id = $request->get('freelancer_id');
            $this->sendNotificationToFreelancer($project, $freelancer_id);
//            $this->sendRequestToFreelancer($project, $request->get('freelancer_id'));
        }
        if($needPay) {
            $amount = $price - $balance;
            $invoice = new Invoice;
            $amount = (int) ($amount / 10);
            $invoice->amount($amount);
            $invoice->detail('t_id', $invoice->getTransactionId());
            return Payment::purchase($invoice, function($driver, $transactionId) use($user, $invoice, $project, $balance){
                Transaction::create([
                    'user_id' =>  $user->id,
                    'transaction_id' => $transactionId,
                    'project_id' => $project->id,
                    'type' => Transaction::PROJECT_TYPE,
                    'status' => Transaction::CREATED_STATUS,
                    'amount' => $invoice->getAmount(),
                    'withdraw_amount' => $balance,
                ]);
            })->pay()->toJson();
        } else {
//            /** @var Conversation $conversation */
//            $conversation = $user->conversations()->create([
//                'user_id' => $project->employer->id,
//                'to_id' => $project->freelancer ? $project->freelancer->id : null,
//                'status' => Conversation::OPEN_STATUS,
//            ]);
            /** @var Wallet $wallet */
            $wallet = $user->wallet;
            $wallet->setBalance((int) ($balance - $price));
//            broadcast(new NewConversationEvent($user, $conversation));
            return new ProjectResource($project);
        }
    }

    public function show($id)
    {
        $project = $this->projectRepository->findOneOrFail($id);
        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, $id)
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var Project $project */
        $project = $this->projectRepository->findOneOrFail($id);
        if($user->can('update-project', $project)) {
           if($request->get('freelancer_id') == $user->id) {
              return $this->accessDeniedResponse();
           }
            /** @var Project $project */
            $project->update($request->only([
                'title',
                'status',
                'description',
                'min_price',
                'max_price',
                'employer_id',
                'freelancer_id',
            ]));
            $props = $request->get('new_properties');
            $skills = $request->get('new_skills');
            $project->properties()->sync([]);
            $project->skills()->sync([]);
            $project->properties()->sync($props);
            $project->skills()->sync($skills);
            $project->save();
            $project = $this->syncAttachments($request, $project);
            return new ProjectResource($project);
        } else {
            $this->accessDeniedResponse();
        }
    }

    public function destroy($id)
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var Project $project */
        $project = $this->projectRepository->findOneOrFail($id);
        if($user->can('delete-project', $project)) {
            try {
                $this->projectRepository->delete($id);
                return \response()->json(['success' => 'پروژه با موفقیت حذف شد', 'id' => $id], 200);
            } catch (\Exception $e) {
                return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }

    private function syncAttachments(Request $request, Project $project)
    {
        $new_ids = $request->get('new_attachments');
        if(count($new_ids) > 0) {
            foreach ($new_ids as $new_id) {
                $upload = Upload::find($new_id);
                if($upload) {
                    $project->attachments()->create([
                        'name' => $upload->name,
                        'path' => $upload->path,
                        'user_id' => auth()->user()->id,
                    ]);
                }
            }
        }
        return $project;
    }

    public function addAttachment(AddProjectAttachmentRequest $request, $id)
    {
        $attachment = $this->projectRepository->addAttachment($request, $id);
        return new AssetResource($attachment);
    }

    public function destroyAttachment($id, $attachment_id)
    {
        return $this->projectRepository->destroyAttachment($id, $attachment_id);
    }

    public function lasts()
    {
        return new ProjectCollectionResource($this->projectRepository->lasts());
    }

    public function requests($id)
    {
        /** @var Project $project */
        $project = $this->projectRepository->findOneOrFail($id);
        return new RequestCollectionResource($project->requests->sortByDesc('created_at'));
    }

    private function sendRequestToFreelancer(Project $project, $freelancer_id)
    {
        /** @var User $freelancer */
        $freelancer = User::find($freelancer_id);
        if($freelancer) {
            ProjectRequest::create([
                'to_id' => $freelancer_id,
                'user_id' => $project->employer->id,
                'title' => $project->title,
                'type' => ProjectRequest::EMPLOYER_TYPE,
                'delivery_date' => 0,
                'description' => '',
                'project_id' => $project->id,
            ]);
        }
    }

    public function projectPayments($id)
    {
        $payments = $this->projectRepository->projectPayments($id);
        return new SecurePaymentCollectionResource($payments->sortByDesc('created_at'));
    }

    public function projectCreatedPayments($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $project = $this->projectRepository->findOneOrFail($id);
        if($auth->can('see-project-payments', $project)) {
            $payments = $this->projectRepository->projectCreatedPayments($id);
            return new SecurePaymentCollectionResource($payments->sortByDesc('created_at'));
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function changePrice(ChangeProjectRequest $request, $id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $project = $this->projectRepository->findOneOrFail($id);
        if($auth->can('change-project-price', $project)) {
            return new ChangePriceResource($this->projectRepository->changePrice($request, $id));
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function acceptOrRejectProjectPayment(AcceptOrRejectChangePrice $changePriceRequest, SecurePayment $payment)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Project $project */
        $project = $payment->project;
        if($auth->can('accept-project-secure-payment', $project)) {
            return $this->projectRepository->acceptOrRejectProjectPayment($changePriceRequest, $payment);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function cancelProjectPayment(SecurePayment $payment)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->can('cancel-project-secure-payment', $payment)) {
            return $this->projectRepository->cancelProjectPayment($payment);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function payProjectPayment(SecurePayment $payment)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->can('pay-project-secure-payment', $payment)) {;
            return $this->projectRepository->payProjectPayment($payment);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function freeProjectPayment(SecurePayment $payment)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->can('free-project-secure-payment', $payment)) {;
            return $this->projectRepository->freeProjectPayment($payment);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function changePriceRequests($id)
    {
        return new ChangePriceRequestCollectionResource($this->projectRepository->changePriceRequests($id));
    }

    public function search(Request $request)
    {
        $term = $request->get('term');
        $limit = $this->getLimit();
        $page = $this->getPage();
        $skills = $request->get('skills');
        if(gettype($skills) == "string") {
            $skills = [$skills];
        }
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
       return new ProjectSearchCollectionResource($this->paginateCollection($projects, $limit, 'page'));
    }

    public function finishProject($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Project $project */
        $project = $this->projectRepository->findOneOrFail($id);
        if($auth->can('finish-project', $project)) {
            $count = SecurePayment::all()->where('project_id', '=', $project->id)->filter(function (SecurePayment $p) {
               return $p->status == SecurePayment::CREATED_STATUS ||
                   $p->status == SecurePayment::ACCEPTED_STATUS ||
                   $p->status == SecurePayment::PAYED_STATUS;
            })->count();
            if($count != 0) {
                return response()->json(['errors' => ['project' => ['پرداخت های امن تایید نشده یا پرداخت نشده یا آزاد نشده دارید']]], 422);
            } else {
                $finished = $this->projectRepository->finishProject($project);
                return response()->json(['finished' => $finished], 200);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function rateFreelancer(RateFreelancerRequest $request, $id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Project $project */
        $project = $this->projectRepository->findOneOrFail($id);
        if($auth->can('rate-freelancer', $project)) {
            $rated = $this->projectRepository->rateFreelancer($request, $project);
            return response()->json(['rated' => $rated], 200);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function rateEmployer(RateFreelancerRequest $request, $id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Project $project */
        $project = $this->projectRepository->findOneOrFail($id);
        if($auth->can('rate-employer', $project)) {
            $rated = $this->projectRepository->rateFreelancer($request, $project);
            return response()->json(['rated' => $rated], 200);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    private function calcProjectPrice(StoreProjectRequest $request, $priceForProject = 0)
    {
        /** @var Collection<ProjectProperty> $properties */
        $properties = ProjectProperty::whereIn('id', $request->get('new_properties'))->get();
        $sum = $properties->sum('price');
        $sum += $priceForProject;
        return $sum;
    }

    public function addProjectPayments(StoreSecurePaymentRequest $request, $id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $project = $this->projectRepository->findOneOrFail($id);
        if($auth->can('add-secure-payments', $project)) {
            return $this->projectRepository->addProjectPayments($request, $id);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    private function sendNotificationToFreelancer(Project $project, $freelancer_id)
    {
        /** @var User $freelancer */
        $freelancer = User::find($freelancer_id);
        if($freelancer) {
            /** @var User $employer */
            $employer = $project->employer;
            $admins = User::all()->filter(function (User $u){
                return $u->hasRole('admin');
            })->pluck('id');
            $ids = collect($admins->values());
            $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
            $users = User::all()->whereIn('id', $ids->toArray());
            Notification::sendNotificationToAll($emails, 'کارفرمای ' . $employer->username . 'پروژه '. $project->title .' برای '. $freelancer->username .' ایجاد کرد', 'کارفرمای ' . $employer->username . 'پروژه '. $project->title .' برای '. $freelancer->username .' ایجاد کرد', null);
            Notification::sendNotificationToUsers($users);
            $project->notifications()->create(array(
                'title' => 'کارفرمای ' . $employer->username . 'پروژه '. $project->title .' برای شما ایجاد کرد',
                'text' => 'کارفرمای ' . $employer->username . 'پروژه '. $project->title .' برای شما ایجاد کرد',
                'type' => Notification::PROJECT,
                'user_id' => $freelancer->id,
                'image_id' => $freelancer->profile->avatar ? $freelancer->profile->avatar->id : null
            ));
            Notification::sendNotificationToAll($emails,  'کارفرمای ' . $employer->username . 'پروژه '. $project->title .' برای شما ایجاد کرد',  'کارفرمای ' . $employer->username . 'پروژه '. $project->title .' برای شما ایجاد کرد', null);
            $users = User::all()->whereIn('id', [$freelancer_id]);
            Notification::sendNotificationToUsers($users);
            $project->notifications()->create(array(
                'title' => 'درخواست انجام پروژه '. $project->title .' برای کاربر ' . $freelancer->username . ' ارسال شد',
                'text' => 'درخواست انجام پروژه '. $project->title .' برای کاربر ' . $freelancer->username . ' ارسال شد',
                'type' => Notification::PROJECT,
                'user_id' => $employer->id,
                'image_id' => $employer->profile->avatar ? $employer->profile->avatar->id : null
            ));
            $users = User::all()->whereIn('id', [$employer->id]);
            foreach ($ids as $id) {
                $project->notifications()->create(array(
                    'title' => 'کارفرمای ' . $employer->username . 'پروژه '. $project->title .' برای '. $freelancer->username .' ایجاد کرد',
                    'text' => 'کارفرمای ' . $employer->username . 'پروژه '. $project->title .' برای '. $freelancer->username .' ایجاد کرد',
                    'type' => Notification::PROJECT,
                    'user_id' => $id,
                    'image_id' => $freelancer->profile->avatar ? $freelancer->profile->avatar->id : null
                ));
            }
            Notification::sendNotificationToUsers($users);
            Notification::sendNotificationToAll($emails, 'پروژه '. 'پروژه '. $project->title .' برای ایجاد شد', 'پروژه '. $project->title .' برای ایجاد شد', null);
        }
    }

    public function sendCancelProjectRequest(CancelProjectRequest $request, Project $project) {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->can('send-cancel-request-project', $project)) {
            $rp = new CancelProjectRequestResource($this->projectRepository->sendCancelProjectRequest($project));
            $emails = collect();
            $emails->add($project->employer);
            $users = collect();
            $users->add($project->employer);
            Notification::sendNotificationToAll($emails, 'درخواست لغو پروژه '. $project->title .' ایجاد شد', 'درخواست لغو پروژه '. $project->title .' ایجاد شد', null);
            Notification::sendNotificationToUsers($users);
            return $rp;
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function acceptCancelProjectRequest(AcceptOrRejectProjectRequest $request, Project $project) {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->can('accept-cancel-request-project', $project)) {
            return $this->projectRepository->acceptCancelProjectRequest($request, $project);
        } else {
            return $this->accessDeniedResponse();
        }
    }
}
