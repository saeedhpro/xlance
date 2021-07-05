<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\DisputeMessageController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectPropertyController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RequestPackageController;
use App\Http\Controllers\SecurePaymentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::post('/login', [UserController::class, 'login'])->name('login');
Route::post('/register', [UserController::class, 'register'])->name('register');

Route::post('/upload', [UploadController::class, 'upload'])->middleware('auth:api')->name('upload');

Route::post('/password/email', [UserController::class, 'sendResetLinkEmail']);
Route::post('/password/check', [UserController::class, 'checkToken']);
Route::post('/password/change', [UserController::class, 'changePassword']);

Route::prefix('/blog')->group(function (){
    Route::get('/categories', [CategoryController::class, 'articleCategories'])->name('blog.categories.index');
    Route::post('/categories', [CategoryController::class, 'storeArticleCategory'])->middleware('auth:api')->name('blog.categories.store');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('blog.categories.show');
    Route::put('/categories/{category}', [CategoryController::class, 'updateArticleCategory'])->middleware('auth:api')->name('blog.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroyArticleCategory'])->middleware('auth:api')->name('blog.categories.delete');
    Route::get('/categories/{category}/articles', [CategoryController::class, 'articles'])->name('categories.articles.index');

    Route::prefix('/articles')->group(function () {
        Route::post('/', [ArticleController::class, 'store'])->middleware('auth:api')->name('articles.store');
        Route::get('/', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('/{article}', [ArticleController::class, 'show'])->name('articles.show');
        Route::put('/{article}', [ArticleController::class, 'update'])->middleware('auth:api')->name('articles.update');
        Route::delete('/{article}', [ArticleController::class, 'destroy'])->middleware('auth:api')->name('articles.delete');
        Route::get('/{article}/comments', [ArticleController::class, 'indexComments'])->name('articles.comments.show');
        Route::post('/{article}/comments', [ArticleController::class, 'storeComment'])->middleware('auth:api')->name('articles.store')->name('articles.comments.store');
        Route::put('/{article}/{comment}', [ArticleController::class, 'updateComment'])->middleware('auth:api')->name('articles.store')->name('articles.comments.update');
        Route::delete('/{article}/{comment}', [ArticleController::class, 'destroyComment'])->middleware('auth:api')->name('articles.store')->name('articles.comments.destroy');
    });
    Route::get('/search/{term}', [ArticleController::class, 'search'])->name('articles.search');

    Route::get('/tags/{tag}/articles', [TagController::class, 'articles'])->name('tags.articles.index');
});
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

Route::prefix('/posts')->middleware('auth:api')->group(function () {
    Route::get('/', [PostController::class, 'index'])->name('posts.index');
    Route::post('/', [PostController::class, 'store'])->name('posts.store');
    Route::get('/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::put('/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/{post}', [PostController::class, 'destroy'])->name('posts.delete');

    Route::post('/{post}/like', [PostController::class, 'like'])->name('posts.like');
    Route::post('/{post}/unlike', [PostController::class, 'unLike'])->name('posts.unLike');
    Route::post('/{post}/save', [PostController::class, 'save'])->name('posts.save');
    Route::post('/{post}/unsave', [PostController::class, 'unSave'])->name('posts.unSave');
    Route::post('/{post}/bookmark', [PostController::class, 'bookmark'])->name('posts.bookmark');
    Route::post('/{post}/unmark', [PostController::class, 'unmark'])->name('posts.unmark');

    Route::prefix('/{post}/comments')->group(function () {
        Route::get('/', [PostController::class, 'indexComments'])->name('posts.comments.index');
        Route::post('/', [PostController::class, 'storeComment'])->name('posts.comments.store');
    });
});

Route::prefix('/comments')->middleware('auth:api')->group(function () {
    Route::get('/{comment}', [PostController::class, 'showComment'])->name('posts.comments.show');
    Route::put('/{comment}', [PostController::class, 'updateComment'])->name('posts.comments.update');
    Route::delete('/{comment}', [PostController::class, 'destroyComment'])->name('posts.comments.destroy');
});

Route::prefix('/stories')->middleware('auth:api')->group(function () {
    Route::get('/', [StoryController::class, 'index'])->name('stories.index');
    Route::get('/own', [StoryController::class, 'ownStories'])->name('stories.own.index');
    Route::post('/', [StoryController::class, 'store'])->name('stories.store');
    Route::get('/{story}', [StoryController::class, 'show'])->name('stories.show');
//    Route::put('/{story}', [StoryController::class, 'update'])->name('stories.update');
    Route::delete('/{story}', [StoryController::class, 'destroy'])->name('stories.delete');
});

Route::prefix('/users')->group(function () {
    Route::get('/freelancers', [UserController::class, 'freelancers'])->name('users.user.freelancers');
    Route::get('/employers', [UserController::class, 'employers'])->name('users.user.employers');
});

//Route::prefix('/users')->group(function () {
//    Route::get('/', [UserController::class, 'all'])->name('users.user.all');
//    Route::get('/{user}', [UserController::class, 'show'])->middleware('auth:api')->name('users.user.show');
//   Route::get('/{user}/posts', [UserController::class, 'posts'])->name('users.user.posts');
////    Route::get('/{user}/posts/liked', [UserController::class, 'userLikedPosts'])->name('users.user.userLikedPosts');
////    Route::get('/{user}/posts/saved', [UserController::class, 'userSavedPosts'])->name('users.user.userSavedPosts');
//=======
Route::prefix('/users')->middleware('auth:api')->group(function () {
    Route::get('/', [UserController::class, 'all'])->name('users.user.all');
    Route::get('/{user}', [UserController::class, 'show'])->name('users.user.show');
    Route::get('/{user}/posts', [UserController::class, 'posts'])->name('users.user.posts');
    Route::get('/{user}/posts/liked', [UserController::class, 'userLikedPosts'])->name('users.user.userLikedPosts');
    Route::get('/{user}/posts/saved', [UserController::class, 'userSavedPosts'])->name('users.user.userSavedPosts');
    Route::get('/{user}/stories', [UserController::class, 'stories'])->name('users.user.stories');
    Route::get('/{user}/projects', [UserController::class, 'userProjects'])->name('users.user.projects');
    Route::get('/{user}/portfolios', [UserController::class, 'userPortfolios'])->name('users.user.userPortfolios');
    Route::post('/{user}/block', [UserController::class, 'adminBlockUser'])->name('users.user.adminBlockUser');
});

Route::prefix('/user')->middleware('auth:api')->group(function () {
    Route::post('/messages', [UserController::class, 'sendMessage'])->name('user.sendMessage');
    Route::get('/messages/new', [UserController::class, 'authNewMessagesCount'])->name('user.authNewMessagesCount');
    Route::get('/conversations', [UserController::class, 'conversations'])->name('user.conversations.index');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('user.createConversations.store');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('user.conversations.show');
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])->name('user.conversations.messages');
});

Route::middleware('auth:api')->group(function () {
    Route::post('/messages/seen', [UserController::class, 'seenMessage'])->name('user.seenMessage');
    Route::post('/conversations/seen', [UserController::class, 'seenConversation'])->name('user.seenConversation');
});

Route::prefix('/user')->middleware('auth:api')->group(function () {
    Route::get('/me', [UserController::class, 'me'])->name('user.me');
    Route::put('/me', [UserController::class, 'updateMe'])->name('user.updateMe');
    Route::put('/password', [UserController::class, 'changeAuthPassword'])->name('user.changeAuthPassword');
    Route::get('/posts', [UserController::class, 'ownPosts'])->name('user.posts');
    Route::get('/posts/liked', [UserController::class, 'ownLikedPosts'])->name('user.ownLikedPosts');
    Route::get('/posts/saved', [UserController::class, 'ownSavedPosts'])->name('user.ownSavedPosts');
    Route::get('/posts/bookmarked', [UserController::class, 'ownBookmarkedPosts'])->name('user.ownBookmarkedPosts');
    Route::get('/posts/followings', [UserController::class, 'ownFollowingsPosts'])->name('user.ownFollowingsPosts');
    Route::get('/stories', [UserController::class, 'ownStories'])->name('user.stories');
    Route::get('/payments/to', [UserController::class, 'securePaymentsToOther'])->name('user.payments.to');
    Route::get('/payments/me', [UserController::class, 'securePaymentsToMe'])->name('user.payments.me');

    Route::put('/profile', [UserController::class, 'updateProfile'])->name('user.profile.update');

    Route::put('/avatar', [UserController::class, 'updateAvatar'])->name('user.avatar.update');
    Route::put('/background', [UserController::class, 'updateBackground'])->name('user.background.update');
    Route::put('/sheba', [UserController::class, 'updateSheba'])->name('user.sheba.update');
    Route::put('/national', [UserController::class, 'updateNationalCard'])->name('user.national.update');

    Route::post('/experiences', [ExperienceController::class, 'store'])->name('user.experiences.store');
    Route::get('/experiences/{experience}', [ExperienceController::class, 'show'])->name('user.experiences.show');
    Route::put('/experiences/{experience}', [ExperienceController::class, 'update'])->name('user.experiences.update');
    Route::delete('/experiences/{experience}', [ExperienceController::class, 'destroy'])->name('user.experiences.destroy');

    Route::post('/educations', [EducationController::class, 'store'])->name('user.educations.store');
    Route::get('/educations/{education}', [EducationController::class, 'show'])->name('user.educations.show');
    Route::put('/educations/{education}', [EducationController::class, 'update'])->name('user.educations.update');
    Route::delete('/educations/{education}', [EducationController::class, 'destroy'])->name('user.educations.destroy');

    Route::post('/achievements', [AchievementController::class, 'store'])->name('user.achievements.store');
    Route::get('/achievements/{achievement}', [AchievementController::class, 'show'])->name('user.achievements.show');
    Route::put('/achievements/{achievement}', [AchievementController::class, 'update'])->name('user.achievements.update');
    Route::delete('/achievements/{achievement}', [AchievementController::class, 'destroy'])->name('user.achievements.destroy');

    Route::prefix('/portfolios')->group(function () {
        Route::get('/', [UserController::class, 'authPortfolios'])->name('user.portfolios.authPortfolios');
        Route::post('/', [PortfolioController::class, 'store'])->name('user.portfolios.store');
        Route::put('/{portfolio}', [PortfolioController::class, 'update'])->name('user.portfolios.update');
        Route::delete('/{portfolio}', [PortfolioController::class, 'destroy'])->name('user.portfolios.destroy');
        Route::post('/{portfolio}/images', [PortfolioController::class, 'addImage'])->name('user.portfolios.addImage');
        Route::delete('/{portfolio}/images/{image}', [PortfolioController::class, 'destroyImage'])->name('user.portfolios.destroyImage');
    });

    Route::get('/skills', [UserController::class, 'skills'])->name('user.skills.index');
    Route::post('/skills/add', [UserController::class, 'addSkill'])->name('user.skills.add');
    Route::post('/skills/remove', [UserController::class, 'removeSkill'])->name('user.skills.remove');

    Route::prefix('/projects')->group(function () {
        Route::get('/', [UserController::class, 'authProjects'])->name('user.projects.authProjects');
        Route::get('/all', [UserController::class, 'authAllProjects'])->name('user.projects.authAllProjects');
        Route::get('/can', [UserController::class, 'authCanDoProjects'])->name('user.projects.authCanDoProjects');
        Route::post('/', [ProjectController::class, 'store'])->name('user.projects.store');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('user.projects.update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('user.projects.destroy');
        Route::get('/{project}/requests', [UserController::class, 'authProjectRequests'])->name('user.projects.authProjectRequests');
        Route::post('/{project}/requests/{request}', [UserController::class, 'authAcceptOrRejectProjectRequest'])->name('user.projects.authAcceptOrRejectProjectRequest');
        Route::get('/{project}/accepted/{accept}', [UserController::class, 'freelancerGetAcceptProjectRequest'])->name('user.projects.accept.get');
        Route::post('/{project}/accepted/{accept}/accept', [UserController::class, 'freelancerAcceptOrRejectRequest'])->name('user.projects.freelancerAcceptOrRejectRequest');
        Route::post('/{project}/attachments', [ProjectController::class, 'addAttachment'])->name('user.projects.addAttachment');
        Route::delete('/{project}/attachments/{attachment}', [ProjectController::class, 'destroyAttachment'])->name('user.projects.destroyAttachment');
    });
    Route::post('/follow', [UserController::class, 'follow'])->name('user.follow');
    Route::post('/unfollow', [UserController::class, 'unFollow'])->name('user.unFollow');

    Route::get('/followers', [UserController::class, 'authFollowers'])->name('user.authFollowers');
    Route::get('/followings', [UserController::class, 'authFollowings'])->name('user.authFollowings');

    Route::prefix('/requests')->group(function () {
        Route::get('/received', [UserController::class, 'authReceivedRequests'])->name('user.authReceivedRequests');
        Route::get('/received/{request}', [UserController::class, 'getReceivedRequest'])->name('user.getReceivedRequest');
//        Route::post('/received/{request}', [UserController::class, 'acceptOrRejectReceivedRequest'])->name('user.acceptOrRejectReceivedRequest');
        Route::get('/sent', [UserController::class, 'authSentRequests'])->name('user.authSentRequests');
        Route::post('/send', [UserController::class, 'sendRequest'])->name('user.sendRequest');
    });

    // payment need
    Route::post('/deposit', [UserController::class, 'deposit'])->name('user.deposit');
    Route::post('/withdraw', [UserController::class, 'withdraw'])->name('user.withdraw.store');
    Route::prefix('/withdraws')->group(function () {
        Route::get('/', [UserController::class, 'withdraws'])->name('user.withdraw.index');
        Route::get('/created', [UserController::class, 'ownCreatedWithdraws'])->name('user.withdraw.created');
        Route::get('/payed', [UserController::class, 'ownPayedWithdraws'])->name('user.withdraw.payed');
        Route::get('/rejected', [UserController::class, 'ownRejectedWithdraws'])->name('user.withdraw.rejected');
        Route::get('/{withdraw}', [UserController::class, 'showWithdraw'])->name('user.withdraw.show');
    });

    Route::get('/wallet', [UserController::class, 'wallet'])->name('user.wallet');

    Route::get('/users/blocked', [UserController::class, 'blocked'])->name('user.users.blockedUsers');
    Route::post('/users/{user}/block', [UserController::class, 'blockAndUnblockUser'])->name('user.users.blockAndUnblockUser');

    Route::prefix('/notifications')->group(function () {
        Route::get('/', [UserController::class, 'notifications'])->name('user.authNotifications');
        Route::post('/', [UserController::class, 'seenNotifications'])->name('user.seenNotifications');
    });

    Route::put('/package', [UserController::class, 'setPackage'])->name('user.package.set');

    Route::prefix('/disputes')->group(function () {
        Route::get('/', [UserController::class, 'disputes'])->name('user.disputes.index');
        Route::post('/', [DisputeController::class, 'store'])->name('user.disputes.store');
        Route::post('/{dispute}', [DisputeController::class, 'show'])->name('user.disputes.show');
        Route::get('/{dispute}/messages', [DisputeController::class, 'messages'])->name('user.disputes.messages.index');
        Route::post('/{dispute}/messages', [DisputeMessageController::class, 'store'])->name('user.disputes.messages.store');
    });
    Route::get('/histories', [UserController::class, 'paymentHistories'])->name('user.paymentHistories.index');

    Route::get('/monthly/income', [UserController::class, 'monthlyIncome'])->name('user.monthly.income');

    Route::prefix('/payments')->group(function () {
        Route::get('/created', [UserController::class, 'ownCreatedSecurePayments'])->middleware('auth:api')->name('user.payments.ownCreatedSecurePayments');
        Route::get('/accepted', [UserController::class, 'ownAcceptedSecurePayments'])->middleware('auth:api')->name('user.payments.ownAcceptedSecurePayments');
        Route::get('/payed', [UserController::class, 'ownPayedSecurePayments'])->middleware('auth:api')->name('user.payments.ownPayedSecurePayments');
        Route::get('/free', [UserController::class, 'ownFreeSecurePayments'])->middleware('auth:api')->name('user.payments.ownFreeSecurePayments');
    });
});
// needs paginate and only show created projects
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/lasts', [ProjectController::class, 'lasts'])->name('projects.lasts');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('user.projects.show');
Route::get('/projects/{project}/requests', [ProjectController::class, 'requests'])->name('user.projects.requests.index');

Route::post('/projects/{project}/cancel', [ProjectController::class, 'sendCancelProjectRequest'])->middleware('auth:api')->name('user.projects.sendCancelProjectRequest');
Route::post('/projects/{project}/cancel/accept', [ProjectController::class, 'acceptCancelProjectRequest'])->middleware('auth:api')->name('user.projects.acceptCancelProjectRequest');

Route::prefix('/projects')->middleware('auth:api')->group(function () {
    Route::get('/{project}/payments', [ProjectController::class, 'projectPayments'])->middleware('auth:api')->name('user.projects.projectPayments');
    Route::get('/{project}/payments/created', [ProjectController::class, 'projectCreatedPayments'])->middleware('auth:api')->name('user.projects.projectCreatedPayments');
    Route::post('/{project}/payments', [ProjectController::class, 'addProjectPayments'])->middleware('auth:api')->name('user.projects.addProjectPayments');

    Route::post('/{project}/finish', [ProjectController::class, 'finishProject'])->name('user.projects.finishProject');
    Route::post('/{project}/rate', [ProjectController::class, 'rateFreelancer'])->name('user.projects.rateFreelancer');
    Route::post('/{project}/rate/employer', [ProjectController::class, 'rateEmployer'])->name('user.projects.rateEmployer');
});

Route::prefix('/portfolios')->group(function () {
    Route::get('/{portfolio}', [PortfolioController::class, 'show'])->name('user.portfolios.show');
    Route::post('/{portfolio}/like', [PortfolioController::class, 'like'])->middleware('auth:api')->name('user.portfolios.like');
    Route::post('/{portfolio}/unlike', [PortfolioController::class, 'unlike'])->middleware('auth:api')->name('user.portfolios.unlike');
});

Route::prefix('/payments')->middleware('auth:api')->group(function () {
    Route::get('{payment}', [SecurePaymentController::class, 'show'])->middleware('auth:api')->name('user.projects.payments.show');
    Route::post('/{payment}/accept', [ProjectController::class, 'acceptOrRejectProjectPayment'])->middleware('auth:api')->name('user.projects.acceptOrRejectProjectPayment');
    Route::delete('/{payment}/cancel', [ProjectController::class, 'cancelProjectPayment'])->middleware('auth:api')->name('user.projects.cancelOrRejectProjectPayment');
    Route::post('/{payment}/pay', [ProjectController::class, 'payProjectPayment'])->middleware('auth:api')->name('user.projects.payProjectPayment');
    Route::post('/{payment}/free', [ProjectController::class, 'freeProjectPayment'])->middleware('auth:api')->name('user.projects.freeOrRejectProjectPayment');
    Route::delete('/{payment}', [SecurePaymentController::class, 'destroy'])->middleware('auth:api')->name('user.projects.payments.destroy');
});

Route::prefix('/requests')->middleware('auth:api')->group(function () {
    Route::get('/{request}', [RequestController::class, 'show'])->name('projects.requests.show');
    Route::post('/{request}/cancel', [RequestController::class, 'cancel'])->name('projects.requests.cancel');
    Route::put('/{request}', [RequestController::class, 'update'])->name('projects.request.update');
    Route::delete('/{request}', [RequestController::class, 'destroy'])->name('projects.request.destroy');
    Route::get('/{request}/payments', [RequestController::class, 'payments'])->name('projects.request.payments');
});

Route::prefix('/properties')->middleware('auth:api')->group(function () {
    Route::get('/', [ProjectPropertyController::class, 'index'])->name('projects.properties.index');
    Route::post('/', [ProjectPropertyController::class, 'store'])->name('projects.properties.store');
    Route::get('/{property}', [ProjectPropertyController::class, 'show'])->name('projects.properties.show');
    Route::put('/{property}', [ProjectPropertyController::class, 'update'])->name('projects.properties.update');
    Route::delete('/{property}', [ProjectPropertyController::class, 'destroy'])->name('projects.properties.destroy');
});

Route::prefix('/skills')->middleware('auth:api')->group(function () {
    Route::post('/', [SkillController::class, 'store'])->name('skills.store');
    Route::get('/categories', [CategoryController::class, 'skillCategories'])->name('skills.categories.index');

    Route::get('/{skill}', [SkillController::class, 'show'])->name('skills.show');
    Route::put('/{skill}', [SkillController::class, 'update'])->name('skills.update');
    Route::delete('/{skill}', [SkillController::class, 'destroy'])->name('skills.destroy');

    Route::post('/categories', [CategoryController::class, 'storeSkillCategory'])->middleware('auth:api')->name('skills.categories.store');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('skills.categories.show');
    Route::put('/categories/{category}', [CategoryController::class, 'updateSkillCategory'])->middleware('auth:api')->name('skill.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroySkillCategory'])->middleware('auth:api')->name('skills.categories.delete');
    Route::get('/categories/{category}/skills', [CategoryController::class, 'skills'])->name('categories.skills.index');
});

Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');
Route::get('/skills/categories', [CategoryController::class, 'skillCategories'])->name('skills.categories.index');

Route::post('/users/{user}/followers', [UserController::class, 'userFollowers'])->name('user.userFollowers');
Route::post('/users/{user}/followings', [UserController::class, 'userFollowings'])->name('user.userFollowings');

Route::prefix('/settings')->group(function (){
    Route::put('/', [SettingController::class, 'change'])->middleware('auth:api')->name('settings.change');
    Route::get('/', [SettingController::class, 'show'])->name('settings.show');
});

Route::prefix('/packages')->middleware('auth:api')->group(function () {
    Route::get('/', [RequestPackageController::class, 'index'])->name('request.package.index');
    Route::post('/', [RequestPackageController::class, 'store'])->name('request.package.store');
    Route::get('/{package}', [RequestPackageController::class, 'show'])->name('request.package.show');
    Route::put('/{package}', [RequestPackageController::class, 'update'])->name('request.package.update');
//    Route::delete('/package', [RequestPackageController::class, 'destroy'])->name('request.package.destroy');
});

Route::prefix('/search')->group(function (){
    Route::get('/user', [UserController::class, 'user'])->name('search.user');
    Route::get('/project', [ProjectController::class, 'search'])->middleware('auth:api')->name('search.project');
    Route::get('/freelancer', [UserController::class, 'search'])->middleware('auth:api')->name('search.freelancer');
    Route::get('/payment/secure/others', [SecurePaymentController::class, 'searchToOthers'])->middleware('auth:api')->name('search.payment.secure.others');
    Route::get('/payment/secure/tome', [SecurePaymentController::class, 'searchToMe'])->middleware('auth:api')->name('search.payment.secure.tome');
});

Route::prefix('/countries')->group(function (){
    Route::get('/', [CountryController::class, 'all'])->name('countries.index');
    Route::post('/', [CountryController::class, 'store'])->middleware('auth:api')->name('countries.store');
    Route::get('/{country}', [CountryController::class, 'show'])->name('countries.show');
    Route::get('/{country}/states', [CountryController::class, 'states'])->name('countries.states');
    Route::get('/{country}/cities', [CountryController::class, 'cities'])->name('countries.cities');
});
Route::prefix('/cities')->group(function (){
    Route::post('/', [CityController::class, 'store'])->middleware('auth:api')->name('cities.store');
    Route::get('/{city}', [CityController::class, 'show'])->name('cities.show');
    Route::get('/{city}/users', [CityController::class, 'users'])->name('cities.users');
    Route::get('/{city}/country', [CityController::class, 'country'])->name('cities.country');
});
Route::prefix('/states')->group(function (){
    Route::post('/', [StateController::class, 'store'])->middleware('auth:api')->name('states.store');
    Route::get('/{state}', [StateController::class, 'show'])->name('states.show');
    Route::delete('/{state}', [StateController::class, 'destroy'])->name('states.destroy');
    Route::get('/{state}/country', [StateController::class, 'country'])->name('states.country');
    Route::get('/{state}/cities', [StateController::class, 'cities'])->name('states.cities');
});

Route::post('/payments/fake', [TagController::class, 'doPay'])->middleware('auth:api');

Route::prefix('/admin')->group(function (){
    Route::get('/users', [AdminController::class, 'users'])->middleware('auth:api');
    Route::get('/users/{user}/portfolios', [AdminController::class, 'userPortfolios'])->middleware('auth:api')->name('user.portfolios.list');
    Route::post('/users/{user}/avatar', [UserController::class, 'acceptOrRejectAvatar'])->middleware('auth:api')->name('user.avatar.accept');
    Route::post('/users/{user}/background', [UserController::class, 'acceptOrRejectBackground'])->middleware('auth:api')->name('user.bg.accept');
    Route::post('/users/{user}/sheba', [UserController::class, 'acceptOrRejectSheba'])->middleware('auth:api')->name('user.sheba.accept');
    Route::post('/users/{user}/national', [UserController::class, 'acceptOrRejectNationalCard'])->middleware('auth:api')->name('user.sheba.national');
    Route::get('/projects', [AdminController::class, 'projects'])->middleware('auth:api');
    Route::post('/projects/{project}/verify', [AdminController::class, 'verifyProjects'])->middleware('auth:api');
    Route::get('/posts', [AdminController::class, 'posts'])->middleware('auth:api');
    Route::get('/stories', [AdminController::class, 'stories'])->middleware('auth:api');
    Route::get('/disputes', [AdminController::class, 'disputes'])->middleware('auth:api');
    Route::post('/disputes/{dispute}/close', [DisputeController::class, 'close'])->middleware('auth:api')->name('user.disputes.close');
    Route::post('/disputes/{dispute}/progress', [DisputeController::class, 'progress'])->middleware('auth:api')->name('user.disputes.progress');
    Route::get('/notifications', [AdminController::class, 'notifications'])->middleware('auth:api');
    Route::get('/portfolios', [AdminController::class, 'portfolios'])->middleware('auth:api');
    Route::post('/portfolios/{portfolio}', [AdminController::class, 'acceptOrRejectPortfolio'])->middleware('auth:api');
    Route::get('/withdraws', [AdminController::class, 'withdraws'])->middleware('auth:api');
    Route::get('/withdraws/{withdraw}', [AdminController::class, 'showWithdraw'])->middleware('auth:api');
    Route::post('/withdraws/{withdraw}', [AdminController::class, 'acceptOrRejectWithdraw'])->middleware('auth:api');
    Route::get('/charts/users', [AdminController::class, 'usersChart'])->middleware('auth:api');
    Route::get('/charts/projects', [AdminController::class, 'projectsChart'])->middleware('auth:api');
    Route::get('/charts/disputes', [AdminController::class, 'disputesChart'])->middleware('auth:api');
    Route::get('/charts/payments', [AdminController::class, 'paymentsChart'])->middleware('auth:api');

    Route::post('/wallet/charge', [AdminController::class, 'chargeUserWallet'])->middleware('auth:api');

    Route::prefix('/search')->group(function (){
        Route::get('/user', [AdminController::class, 'searchUser'])->name('search.user');
        Route::get('/project', [AdminController::class, 'searchProject'])->middleware('auth:api')->name('search.project');
    });
});
Route::get('/email/verify/{id}/{hash}', [UserController::class, 'verifyEmail']);


Route::prefix('/notifications')->group(function () {
    Route::get('/{notification}', [UserController::class, 'showNotification'])->name('user.showNotification');
});
