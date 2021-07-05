<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotificationMail extends Notification
{
    use Queueable;

    private $user;
    private $token;

    /**
     * Create a new notification instance.
     *
     * @param $user
     * @param $token
     */
    public function __construct(User $user, $token)
    {
        $this->user = $user;
        $this->token = $token;
        parent::__construct($token, $user);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $link = 'https://xlance.ir/auth/Password-recovery/'.$this->token.'?email'.$this->user->email;
        return (new MailMessage)
            ->subject('ارسال لینک تغییر پسورت')
                    ->line('برای تغییر پسورد روی لینک زیر کلیک کنید!')
                    ->action('Notification Action', $link);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
