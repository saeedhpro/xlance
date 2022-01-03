<?php

namespace App\Models;

use App\Events\NewNotificationEvent;
use App\Http\Resources\NotifiableUserResource;
use App\Http\Resources\PortfolioResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\StoryResource;
use App\Http\Resources\UserResource;
use App\Jobs\SendNotification;
use App\Jobs\SendPushNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    const POST = 'post';
    const STORY = 'story';
    const PROJECT = 'project';
    const FREELANCER = 'freelancer';
    const EMPLOYER = 'employer';
    const PORTFOLIO = 'portfolio';
    const WITHDRAW = 'withdraw';
    const SKILLS = 'skills';
    const CREATE_PROJECT = 'create_project';
    const AVATAR_DENIED = 'avatar_denied';
    const AVATAR_ACCEPTED = 'avatar_accepted';
    const BG_DENIED = 'bg_denied';
    const BG_ACCEPTED = 'bg_accepted';
    const NATIONAL_DENIED = 'national_denied';
    const NATIONAL_ACCEPTED = 'national_accepted';
    const PORTFOLIO_DENIED = 'portfolio_denied';
    const PORTFOLIO_ACCEPTED = 'portfolio_accepted';
    const PASSWORD_CHANGED = 'password_changed';
    const FOLLOW = 'follow';
    const RATE_FREELANCER = 'rate_freelancer';
    const ِDISPUTE = 'dispute';
    const WALLET = 'wallet';
    const SHEBA_ACCEPTED = 'sheba_accepted';
    const SHEBA_DENIED = 'sheba_denied';
    const PACKAGE = 'package';
    const REGISTER = 'register';
    const ADMIN_REGISTER = 'admin_register';
    const ADMIN_AVATAR_CREATED = 'admin_avatar_created';
    const ADMIN_BG_CREATED = 'admin_bg_created';
    const ADMIN_NATIONAL_CREATED = 'admin_national_created';
    const ADMIN_PORTFOLIO_CREATED = 'admin_portfolio_created';
    const ADMIN_PROJECT = 'admin_project';
    const ADMIN_POST = 'admin_post';
    const ADMIN_RECORDS = 'admin_records';
    const ADMIN_SHEBA = 'admin_sheba';
    const ADMIN_PACKAGE = 'admin_package';

    protected $fillable = [
        'title',
        'text',
        'type',
        'user_id',
        'image_id',
        'notifiable_id',
        'notifiable_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable', 'imageable_type', 'id', 'image_id');
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function getContent()
    {
        switch ($this->type) {
            case Notification::POST:
                return new PostResource($this->notifiable);
            case Notification::STORY:
                return new StoryResource($this->notifiable);
            case Notification::PROJECT:
                return new ProjectResource($this->notifiable);
            case Notification::EMPLOYER:
            case Notification::FREELANCER:
                return new NotifiableUserResource($this->notifiable);
            case Notification::PORTFOLIO:
                return new PortfolioResource($this->notifiable);
            default:
                return null;
        }
    }

    public static function sendNotification($email, $title, $content, $customContent)
    {

        $TOKEN = "0409b4ebd95972d2b0861f021eef37644ff0d57c";

        $data = array(
            "app_ids" => ["ld83q4r2lv8mrzqe",],
            "data" => array(
                "title" => $title,
                "content" => $content,
                "custom_content" => $customContent,
                "action" => array(
                    "action_type" => "U",
                    "url" => "https://pushe.co"
                ),
                "filters" => array(
                    "email" => [$email]
                ),
                "buttons" => array(
                    array(
                        "btn_action" => array(
                            "action_type" => "U",
                            "url" => "https://pushe.co"
                        ),
                        "btn_content" => "YOUR_CONTENT",
                        "btn_order" => 0,
                    ),
                    array(
                        "btn_action" => array(
                            "action_type" => "U",
                            "url" => "https://pushe.co"
                        ),
                        "btn_content" => "YOUR_CONTENT",
                        "btn_order" => 1,
                    )
                ),
            ),
        );

        // initialize curl
        $ch = curl_init("https://api.pushe.co/v2/messaging/notifications/web/");

        // set header parameters
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Token " . $TOKEN,
        ));
        curl_setopt($ch, CURLOPT_POST, 1);

        // set data
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        return curl_exec($ch);
    }
    public static function sendNotificationToAll($emails, $title, $content, $customContent)
    {
        SendPushNotification::dispatch($title, $content, $customContent, $emails);
        return true;
    }
    public static function sendNotificationToUsers(Collection $users)
    {
        foreach ($users as $user){
            Notification::broadcast($user);
        }
    }
    public static function sendNotificationToUser($user)
    {
        Notification::broadcast($user);
    }

    private static function broadcast(User $user) {
        $count = $user->new_notifications;
        $count = $count + 1;
        $user->update([
            'new_notifications' => $count
        ]);
        $nonce = Notification::getNonce();
        broadcast(new NewNotificationEvent($user, $count, $nonce));
    }
    public static function getNonce(): int
    {
        try {
            $nonce = random_int(0, 2 ^ 1024);
        } catch (\Exception $e) {
            $nonce = 1024;
        }
        return $nonce;
    }
}
