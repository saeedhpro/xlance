<?php

use App\Http\Controllers\PaymentController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('/payment')->group(function () {
    Route::get('/verify', [PaymentController::class, 'verify']);
});

Route::get('/email/verify', function () {
    $id = \request()->get('id');
    $hash = \request()->get('signature');
    $route = 'https://xlance.ir/email/verify/' . $id . '/' . $hash;
    return Redirect::away($route);
})->name('verification.notice');

Route::get('/password/change', function () {
    $email = \request()->get('email');
    $token = \request()->get('token');
    $route = 'https://xlance.ir/password/change?email=' . $email . '&token=' . $token;
    return Redirect::away($route);
})->name('password.change');

Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {
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
})->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return response()->json(['data' => 'لینک فعال سازی ایمیل برای شما ارسال شد'], 200);
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
