<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationToUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->notification->is_admin) {
            $admins = $this->getAdminUsers();
            foreach ($admins as $admin) {
                Notification::sendNotificationToUser($admin);
            }
        } else {
            $user = $this->notification->user;
            Notification::sendNotificationToUser($user);
        }
    }
    private function getAdminUsers() {
        return User::query()->with('roles')->whereHas('roles', function ($q) {
            $q->where('name', '=', 'admin');
        })->get();
    }
}
