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

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $title;
    private $content;
    private $customContent;
    private $emails;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($title, $content, $customContent, $emails)
    {
        $this->title = $title;
        $this->content = $content;
        $this->customContent = $customContent;
        $this->emails = $emails;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $TOKEN = "0409b4ebd95972d2b0861f021eef37644ff0d57c";

        $data = array(
            "app_ids" => ["ld83q4r2lv8mrzqe",],
            "data" => array(
                "title" => $this->title,
                "content" => $this->content,
                "custom_content" => $this->customContent,
                "action" => array(
                    "action_type" => "U",
                    "url" => "https://pushe.co"
                ),
                "filters" => array(
                    "email" => $this->emails
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
    }
}
