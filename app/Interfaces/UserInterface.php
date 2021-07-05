<?php


namespace App\Interfaces;

use App\Http\Requests\AcceptOrRejectProjectRequest;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SeenConversationRequest;
use App\Http\Requests\SeenMessageRequest;
use App\Http\Requests\SendRequestForProjectRequest;
use App\Http\Requests\SetPackageRequest;
use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateShebaRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AcceptFreelancerRequest;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Request as ProjectRequest;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Interface UserInterface
 * @package App\Interfaces;
 */
interface UserInterface extends BaseInterface
{
    public function register(RegisterRequest $request);
    public function login(LoginRequest $request);
    public function setRegisteredUserRole(Request $request, User $user);

    public function updateProfile(UpdateProfileRequest $request, User $user);
    public function updateAvatar(UpdateAvatarRequest $request, Profile $profile);
    public function updateBackground(UpdateAvatarRequest $request, Profile $profile);
    public function updateNationalCard(UpdateAvatarRequest $request, Profile $profile);

    public function authProjects();
    public function authCanDoProjects();
    public function userProjects($id);

    public function authPortfolios();
    public function userPortfolios($id);

    public function userFollowers($id);
    public function userFollowings($id);

    public function authReceivedRequests();
    public function authSentRequests();

    public function userReceivedRequests($id);
    public function userSentRequests($id);

    public function getReceivedRequest($id);
//    public function acceptOrRejectReceivedRequest(AcceptOrRejectProjectRequest $projectRequest, ProjectRequest $request);

    public function authProjectRequests($id);
    public function authAcceptOrRejectProjectRequest(AcceptOrRejectProjectRequest $projectRequest, Project $project, ProjectRequest $request);
    public function freelancerAcceptOrRejectRequest(AcceptOrRejectProjectRequest $projectRequest, Project $project, AcceptFreelancerRequest $freelancerRequest);

    public function sendRequest(SendRequestForProjectRequest $requestForProjectRequest);

    public function deposit($amount);
    public function withdraw($amount);
    public function wallet();

    public function ownLikedPosts();
    public function userLikedPosts($id);

    public function ownSavedPosts();
    public function userSavedPosts($id);

    public function ownBookmarkedPosts();
    public function userBookmarkedPosts($id);

    public function blockAndUnblockUser($id);
    public function blockedUsers();

    public function disputes();

    public function freelancers();
    public function employers();

    public function authNotifications();

    public function changeAuthPassword(UpdatePasswordRequest $request);

    public function updateMe(UpdateUserRequest $request, User $user);

    public function acceptOrRejectAvatar(AcceptOrRejectProjectRequest $request, Profile $profile);
    public function acceptOrRejectBackground(AcceptOrRejectProjectRequest $request, Profile $profile);
    public function acceptOrRejectNationalCard(AcceptOrRejectProjectRequest $request, Profile $profile);
    public function acceptOrRejectSheba(AcceptOrRejectProjectRequest $request, Profile $profile);
    public function updateSheba(UpdateShebaRequest $request, Profile $profile);

    public function monthlyIncome();

    public function ownCreatedSecurePayments();
    public function ownAcceptedSecurePayments();
    public function ownPayedSecurePayments();
    public function ownFreeSecurePayments();

    public function ownCreatedWithdraws();
    public function ownPayedWithdraws();
    public function ownRejectedWithdraws();

    public function seenMessage(SeenMessageRequest $seenMessageRequest);
    public function seenConversation(SeenConversationRequest $seenConversationRequest);

    public function authNewMessagesCount();

}
