<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class ValidateUserJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new job instance.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $profile = $this->user->profile;
        $validated = $this->user->first_name !== null &&
            $this->user->last_name !== null &&
            $this->user->phone_number !== null &&
            $profile->sheba_accepted !== 1 &&
            $profile->national_card_accepted === 1;
        $role = Role::findOrCreate('freelancer', 'web');
        if($validated) {
            $this->user->assignRole($role);
        }
    }
}
