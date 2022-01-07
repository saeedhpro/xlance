<?php

namespace App\Observers;

use App\Models\Notification;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class ProfileObserver
{
    /**
     * Handle the Profile "created" event.
     *
     * @param \App\Models\Profile $profile
     * @return void
     */
    public function created(Profile $profile)
    {
        //
    }

    /**
     * Handle the Profile "updated" event.
     *
     * @param \App\Models\Profile $profile
     * @return void
     */
    public function updated(Profile $profile)
    {
        /** @var User $user */
        $user = $profile->user;
        $validated = $user->first_name !== null &&
            $user->last_name !== null &&
            $user->phone_number !== null &&
            $profile->sheba !== null &&
            $profile->national_card !== null &&
            $profile->sheba_accepted !== 1 &&
            $profile->national_card_accepted === 1;
        $role = Role::findByName('freelancer', 'web');
        if ($validated) {
            $profile->user->assignRole($role);
        }
        $text = 'کاربر ' . $user->username . ' تایید شد';
        $type = Notification::EMPLOYER;
        Notification::make(
            $type,
            $text,
            null,
            $text,
            get_class($user),
            $user->id,
            true
        );
    }

    /**
     * Handle the Profile "deleted" event.
     *
     * @param \App\Models\Profile $profile
     * @return void
     */
    public function deleted(Profile $profile)
    {
        //
    }

    /**
     * Handle the Profile "restored" event.
     *
     * @param \App\Models\Profile $profile
     * @return void
     */
    public function restored(Profile $profile)
    {
        //
    }

    /**
     * Handle the Profile "force deleted" event.
     *
     * @param \App\Models\Profile $profile
     * @return void
     */
    public function forceDeleted(Profile $profile)
    {
        //
    }
}
