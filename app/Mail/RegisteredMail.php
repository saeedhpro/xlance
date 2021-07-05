<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class RegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    private $user;

    /**
     * Create a new message instance.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $route = URL::temporarySignedRoute('verification.notice',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->user->id
            ]
        );
        return $this->view('mails.mail')
            ->with([
                'username' => $this->user->username,
                'route' => $route
            ]);
    }
}
