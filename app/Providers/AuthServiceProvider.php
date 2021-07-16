<?php

namespace App\Providers;

use App\Models\AcceptFreelancerRequest;
use App\Models\CancelProjectRequest;
use App\Models\ChangeProjectRequest;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\Request;
use App\Models\SecurePayment;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('destroy-post', function ($user, $post) {
            return $user->id == $post->user_id || $user->hasRole('admin');
        });

        Gate::define('update-post', function ($user, $post) {
            return $user->id == $post->user_id || $user->hasRole('admin');
        });

        Gate::define('destroy-story', function ($user, $story) {
            return $user->id == $story->user_id || $user->hasRole('admin');
        });

        Gate::define('update-story', function ($user, $story) {
            return $user->id == $story->user_id || $user->hasRole('admin');
        });

        Gate::define('update-comment', function ($user, $comment) {
            return $user->id == $comment->user_id || $user->hasRole('admin');
        });

        Gate::define('destroy-comment', function ($user, $comment) {
            return $user->id == $comment->user_id || $user->hasRole('admin');
        });

        Gate::define('update-experience', function ($user, $experience) {
            return $user->id == $experience->user_id || $user->hasRole('admin');
        });
        Gate::define('destroy-experience', function ($user, $experience) {
            return $user->id == $experience->user_id || $user->hasRole('admin');
        });

        Gate::define('update-education', function ($user, $education) {
            return $user->id == $education->user_id || $user->hasRole('admin');
        });
        Gate::define('destroy-education', function ($user, $education) {
            return $user->id == $education->user_id || $user->hasRole('admin');
        });

        Gate::define('update-achievement', function ($user, $achievement) {
            return $user->id == $achievement->user_id || $user->hasRole('admin');
        });
        Gate::define('destroy-achievement', function ($user, $achievement) {
            return $user->id == $achievement->user_id || $user->hasRole('admin');
        });

        Gate::define('update-portfolio', function ($user, $portfolio) {
            return $user->id == $portfolio->user_id || $user->hasRole('admin');
        });
        Gate::define('destroy-portfolio', function ($user, $portfolio) {
            return $user->id == $portfolio->user_id || $user->hasRole('admin');
        });

        Gate::define('update-project', function ($user, $project) {
            return $user->id == $project->user_id || $user->hasRole('admin');
        });
        Gate::define('destroy-project', function ($user, $project) {
            return $user->id == $project->user_id || $user->hasRole('admin');
        });

        Gate::define('follow-user', function ($user, $follow) {
            return $user->id !== $follow->id;
        });
        Gate::define('unFollow-user', function ($user, $unFollow) {
            return $user->id !== $unFollow->id;
        });

        Gate::define('rate-freelancer', function ($user, $project) {
            return $user->id == $project->employer->id && $project->status == Project::FINISHED_STATUS;
        });

        Gate::define('rate-employer', function ($user, $project) {
            return $user->id == $project->freelancer->id && $project->status == Project::FINISHED_STATUS;
        });

        Gate::define('finish-project', function ($user, Project $project) {
            return $user->id == $project->employer->id && $project->status == Project::STARTED_STATUS;
        });

        Gate::define('send-request-for-project', function (User $user, Project $project) {
            $project_ids = $user->sentRequests()->pluck('project_id')->toArray();
            $c = true;
            if(in_array($project->id, $project_ids)){
               $c = false;
            }
            return $user->number > 0 && $user->id !== $project->employer->id && $project->freelancer == null && $c && $user->isValidated() && !$user->hasRole('admin');
        });

        Gate::define('accept-or-reject-request-for-project', function (User $user, Request $request) {
            /** @var Project $project */
            $project = $request->project;
            return $project->acceptFreelancerRequest()->count() == 0 && $project->selected_request_id == null && $user->id == $project->employer->id;
        });

        Gate::define('freelancer-accept-or-reject-request', function (User $user, AcceptFreelancerRequest $freelancerRequest) {
            /** @var Project $project */
            $project = $freelancerRequest->project;
            /** @var User $freelancer */
            $freelancer = $freelancerRequest->freelancer;
            return $project->selected_request_id == null && $project->freelancer_id == null &&
                $freelancer->id == $user->id &&
                $freelancerRequest->status == AcceptFreelancerRequest::CREATED_STATUS;
        });

//        Gate::define('accept-or-reject-received-request', function (User $user, Request $request) {
//            return $user->id === $request->to_id && $user->isValidated();
//        });

        Gate::define('block-user', function (User $user, User $block) {
            return $user->id !== $block->id && !$block->hasRole('admin');
        });

        Gate::define('change-project-price', function (User $user, Project $project) {
            $hasNotRequest = $project->createdPriceRequests()->where('sender_id', '=', $user->id)->count() === 0;
            return $hasNotRequest && $user->hasAnyRole(['employer', 'freelancer']) && $project->freelancer !== null
                && ($user->id === $project->freelancer->id || $user->id === $project->employer->id);
        });

        Gate::define('accept-project-price', function (User $user, ChangeProjectRequest $request) {
            return $user->id !== $request->sender->id && $user->id === $request->receiver->id;
        });

        Gate::define('create-dispute', function (User $user, Project $project) {
            return $user->id === $project->freelancer->id || $user->id === $project->employer->id;
        });

        Gate::define('change-password', function ($user, $auth) {
            return $user->id == $auth->id || $auth->hasRole('admin');
        });

        Gate::define('delete-experience', function ($user, $experience) {
            return $user->id == $experience->user_id;
        });

        Gate::define('delete-education', function ($user, $education) {
            return $user->id == $education->user_id;
        });

        Gate::define('delete-achievement', function ($user, $achievement) {
            return $user->id == $achievement->user_id;
        });

        Gate::define('create-direct-conversation', function (User $user, $id) {
            $count = $user->conversations()->where('type', '=', Conversation::DIRECT_TYPE)->where('to_id', '=', $id)->count();
            return $count == 0 && $user->id !== $id;
        });

        Gate::define('add-secure-payments', function (User $user, Project $project) {
            return $user->hasRole('freelancer') && $project->status == Project::STARTED_STATUS && $user->id === $project->freelancer->id;
        });

        Gate::define('accept-project-secure-payment', function (User $user, Project $project) {
            return $user->hasRole('employer') && $project->status == Project::STARTED_STATUS && $user->id === $project->employer->id;
        });

        Gate::define('pay-project-secure-payment', function (User $user, SecurePayment $payment) {
            /** @var Project $project */
            $project = $payment->project;
            return $user->hasRole('employer') && $payment->status == SecurePayment::ACCEPTED_STATUS && $user->id === $project->employer->id;
        });

        Gate::define('free-project-secure-payment', function (User $user, SecurePayment $payment) {
            /** @var Project $project */
            $project = $payment->project;
            return $user->hasRole('employer') && $payment->status == SecurePayment::PAYED_STATUS && $user->id === $project->employer->id;
        });

        Gate::define('cancel-project-secure-payment', function (User $user, SecurePayment $payment) {
            return $user->hasRole('freelancer') && ($payment->status == SecurePayment::CREATED_STATUS || $payment->status == SecurePayment::ACCEPTED_STATUS) && $user->id === $payment->user->id;
        });

        Gate::define('see-project-payments', function (User $user, Project $project) {
            return $user->hasAnyRole(['employer', 'admin']) && $user->id === $project->employer->id;
        });

        Gate::define('send-cancel-request-project', function (User $user, Project $project) {
            $count = $project->cancelRequests()
                ->where('freelancer_id', '=', $user->id)
                ->where(function ($q) {
                    $q->where('status', '=', CancelProjectRequest::CREATED_STATUS);
                })->count();
            return $project->status == Project::STARTED_STATUS && $project->freelancer && $user->id === $project->freelancer->id && $count === 0;
        });

        Gate::define('accept-cancel-request-project', function (User $user, Project $project) {
            return $user->id === $project->employer->id;
        });

        Gate::define('freelancer-get-accept-reject-request', function (User $user, AcceptFreelancerRequest $accept) {
            return $user->id === $accept->freelancer->id;
        });
    }
}
