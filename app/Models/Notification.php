<?php

namespace App\Models;

use App\Events\NewNotificationEvent;
use App\Http\Resources\PortfolioResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\StoryResource;
use App\Http\Resources\UserResource;
use App\Jobs\SendNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    const PROFILE_BUSINESS_TYPE = 'profile_business';
    const REQUEST_COUNT_TYPE = 'request_count';
    const PROJECT_DISPUTED_TYPE = 'project_disputed';
    const POST_ACCEPTED_TYPE = 'post_accepted';
    const POST_REJECTED_TYPE = 'post_rejected';
    const POST_CREATED_TYPE = 'post_created';
    const STORY_CREATED_TYPE = 'story_created';
    const STORY_ACCEPTED_TYPE = 'story_accepted';
    const STORY_REJECTED_TYPE = 'story_rejected';
    const USER_BLOCKED_TYPE = 'user_blocked';
    const POST_LIKED_TYPE = 'post_liked';
    const POST_COMMENTED_TYPE = 'post_commented';
    const PORTFOLIO_LIKED_TYPE = 'portfolio_liked';
    const FOLLOW_TYPE = 'follow';
    const UNFOLLOW_TYPE = 'unfollow';
    const PAYMENT_PAYED_TYPE = 'payment_payed';
    const SECURE_PAYMENT_CREATED_TYPE = 'secure_payment_created';
    const SECURE_PAYMENT_ACCEPTED_TYPE = 'secure_payment_accepted';
    const SECURE_PAYMENT_PAYED_TYPE = 'secure_payment_payed';
    const PROJECT_PUBLISHED_TYPE = 'project_published';
    const PROJECT_REJECTED_TYPE = 'project_rejected';
    const PROJECT_CANCELED_TYPE = 'project_canceled';
    const PROJECT_FINISHED_TYPE = 'project_finished';
    const PROJECT_CREATED_FOR_FREELANCER = 'project_created_for_freelancer';
    const REQUEST_ACCEPTED_TYPE = 'request_accepted';
    const REQUEST_RECEIVED_TYPE = 'request_received';

    const POST = 'post';
    const STORY = 'story';
    const PROJECT = 'project';
    const FREELANCER = 'freelancer';
    const EMPLOYER = 'employer';
    const PORTFOLIO = 'portfolio';
    const WITHDRAW = 'withdraw';

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
                return new UserResource($this->notifiable);
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
                    "email" => $emails
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

        curl_exec($ch);
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
