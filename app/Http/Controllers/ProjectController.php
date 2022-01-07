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
            $employer = $project->employer;
            $freelancer = $project->freelancer;
            $type = Notification::PROJECT;
            $text =  'پروژه '. $project->title .' ایجاد شد.';
            Notification::make(
                $type,
                $text,
                $employer->id,
                $text,
                get_class($project),
                $project->id,
                false
            );

            $type = Notification::PROJECT;
            $text =  "$employer->first_name $employer->last_name پروژه ی $project->title را برای $freelancer->first_name $freelancer->last_name ایجاد کرد.";
            Notification::make(
                $type,
                $text,
                null,
                $text,
                get_class($project),
                $project->id,
                true
            );
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
        $exp = Carbon::now()->subDays(14);
        $projects = Project::query()->with(['properties', 'skills'])->where('status', '=', Project::PUBLISHED_STATUS)
            ->orderByDesc('created_at')->where(function ($query) use($exp){
                $query->whereDate('created_at', '>', $exp);
            });
        if($term) {
            $projects = $projects->where(function ($query) use($term) {
                $query->where('title', 'like', '%'.$term.'%');
            });
        }
        if($min_price) {
            $projects = $projects->where(function ($query) use($min_price) {
                $query->where('min_price', '>=', $min_price);
            });
        }
        if($max_price) {
            $projects = $projects->where(function ($query) use($max_price) {
                $query->where('max_price', '>=', $max_price);
            });
        }
        if($skills && count($skills) > 0) {
            $projects = $projects->where(function ($query) use ($skills) {
                $query->whereHas('skills', function ($q) use ($skills) {
                    $q->whereIn('skills.id', $skills);
                });
            });
        }
       return new ProjectSearchCollectionResource($projects->paginate(10));
//       return new ProjectSearchCollectionResource($this->paginateCollection($projects->get(), $limit, 'page'));
    }

    public function finishProject($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Project $project */
        $project = $this->projectRepository->findOneOrFail($id);
        if($auth->can('finish-project', $project)) {
            $request = ProjectRequest::findOrFail($project->selected_request_id);
            $count = SecurePayment::query()->where(function ($q) use($project, $request) {
                $q->where('project_id', '=', $project->id);
                $q->where('request_id', '=', $request->id);
                $q->whereIn('status', [
                    SecurePayment::CREATED_STATUS,
                    SecurePayment::ACCEPTED_STATUS,
                    SecurePayment::PAYED_STATUS
                ]);
            })->count();
            if($count != 0) {
                return response()->json(['errors' => ['project' => ['پرداخت های امن تایید نشده یا پرداخت نشده یا آزاد نشده دارید']]], 422);
            } else {
                $finished = $this->projectRepository->finishProject($project);
                $type = Notification::ِDISPUTE;
                $text = $auth->first_name . '' . $auth->last_name . 'برای پروژه ' . $project->title . ' اتمام پروژه را تایید کرده است.';
                Notification::make(
                    $type,
                    $text,
                    $project->freelancer->id,
                    $text,
                    get_class($project),
                    $project->id,
                    false
                );
                $type = Notification::ADMIN_PROJECT;
                $text = $auth->first_name . '' . $auth->last_name . 'برای پروژه ' . $project->title . ' اتمام پروژه را تایید کرده است.';
                Notification::make(
                    $type,
                    $text,
                    null,
                    $text,
                    get_class($project),
                    $project->id,
                    true
                );
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
            /** @var User $emp */
            $emp = $project->employer()->get();
            $type = Notification::PROJECT;
            $text =  'پروژه ی '. $project->title .' برای '. $freelancer->first_name . ' ' . $freelancer->last_name .' ایجاد شد.';
            Notification::make(
                $type,
                $text,
                $emp->id,
                $text,
                get_class($project),
                $project->id,
                false
            );
            $type = Notification::PROJECT;
            $text =  $emp->first_name . ' ' . $emp->last_name . ' پروژه ی ' . $project->title . ' را برای شما ایجاد کرده است';
            Notification::make(
                $type,
                $text,
                $freelancer->id,
                $text,
                get_class($project),
                $project->id,
                false
            );
        }
    }

    public function sendCancelProjectRequest(CancelProjectRequest $request, Project $project) {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->can('send-cancel-request-project', $project)) {
            $rp = new CancelProjectRequestResource($this->projectRepository->sendCancelProjectRequest($project));
            $type = Notification::PROJECT;
            $text =  'درخواست لغو پروژه '. $project->title .' ایجاد شد';
            Notification::make(
                $type,
                $text,
                $project->employer->id,
                $text,
                get_class($project),
                $project->id,
                false
            );
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
