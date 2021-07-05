<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SendResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    private $user;
    private $token;

    /**
     * Create a new message instance.
     *
     * @param User $user
     */
    public function __construct(User $user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $route = URL::temporarySignedRoute('password.change',
            Carbon::now()->addMinutes(60),
            [
                'email' => $this->user->email,
                'token' => $this->token,
            ]
        );
        return $this->view('mails.reset-password')
            ->with([
                'username' => $this->user->username,
                'route' => $route
            ]);
    }
}
