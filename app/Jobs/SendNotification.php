<?php

namespace App\Jobs;

use App\Events\NewNotificationEvent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use phpDocumentor\Reflection\Types\This;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    /**
     * Create a new job instance.
     *
     * @return void
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
        $count = $this->user->new_notifications;
        $count = $count + 1;
        $this->user->update([
            'new_notifications' => $count
        ]);
        $nonce = $this->getNonce();
        broadcast(new NewNotificationEvent($this->user, $count, $nonce));
    }

    public function getNonce(): int
    {
        try {
            $nonce = random_int(0, 2 ^ 1024);
        } catch (\Exception $e) {
            $nonce = 1024;
        }
        return $nonce;
    }
}
