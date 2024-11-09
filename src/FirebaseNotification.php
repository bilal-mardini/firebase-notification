<?php
namespace BilalMardini\FirebaseNotification;

use App\Models\User;
use App\Models\Notification;
use App\Models\UserFcmToken;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FirebaseNotification
{
    private string $title;
    private string $body;
    private ?string $icon = null;
    private ?string $topic = null;
    private array $tokens = [];
    private array $userIds = [];
    private array $data = [];

    /**
     * Set the title of the notification.
     *
     * @param string $title The title of the notification.
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the body of the notification.
     *
     * @param string $body The body of the notification.
     * @return $this
     */
    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set the icon of the notification.
     *
     * @param string $icon The image URL of the notification icon.
     * @return $this
     */
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Set the topic of the notification.
     *
     * @param string $topic The topic of the notification.
     * @return $this
     */
    public function setTopic(string $topic): self
    {
        $this->topic = $topic;
        return $this;
    }

    /**
     * Set the users collection and extract tokens and IDs.
     */
    public function setUsers($users): self
    {
        $this->tokens = UserFcmToken::whereIn('user_id', $users->pluck('id'))->pluck('fcm_token')->toArray();
        $this->userIds = $users->pluck('id')->toArray();
        return $this;
    }

    /**
     * Set the data of the notification.
     *
     * @param array $data The additional data to send with the notification.
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Build the payload for the notification.
     *
     * @return array The payload for the notification.
     */
    private function buildPayload(string $token): array
    {
        $message = [
            'data' => [
                'title' => $this->title,
                'body' => $this->body,
                'additional_data'=>$this->data
            ],
            'android' => [
                'notification' => [
                    'image' => $this->icon ?? ''
                ]
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'mutable-content' => 1
                    ]
                ],
                'fcm_options' => [
                    'image' => $this->icon ?? ''
                ]
            ],
        ];

        if ($this->topic) {
            $message['topic'] = $this->topic;
        } else {
            $message['token'] = $token;
        }

        return ['message' => $message];
    }

    /**
     * Send the notification to the Firebase Cloud Messaging API.
     *
     * @param array $payload The payload to send to the API.
     * @return bool Whether the request was successful.
     */
    private function send(array $payload): bool
    {
        $apiUrl = 'https://fcm.googleapis.com/v1/projects/' . config('firebase.project_id') . '/messages:send';
        $accessToken = AccessToken::getToken();

        $response = Http::withHeaders([
            "Authorization" => "Bearer $accessToken",
            'Content-Type' => 'application/json',
        ])->post($apiUrl, $payload);

        $statusCode = $response->status();
        $responseBody = $response->json();

        if ($statusCode !== 200) {
            Log::error('Firebase notification failed', [
                'status' => $statusCode,
                'response' => $responseBody,
                'payload' => $payload
            ]);
            return false;
        }

        return true;
    }

    /**
     * Send the notification to the specified topic or users.
     *
     * @return bool Whether the request was successful.
     */
    public function push(): bool
    {
        AccessToken::initialize(
            config('firebase.credentials_file_path'),
            config('firebase.project_id')
        );

        foreach ($this->tokens as $token) {
            $payload = $this->buildPayload($token);
            $responseCode = $this->send($payload);

            if ($responseCode !== true) {
                return false;
            }
        }

        $this->saveNotification($this->topic !== null);

        return true;
    }

    /**
     * Save the notification in the database.
     *
     * @param bool $isGeneral Whether the notification is a general notification or not.
     */
    private function saveNotification(bool $isGeneral): void
    {
        $notification = Notification::create([
            'title' => $this->title,
            'description' => $this->body,
            'is_general' => $isGeneral,
        ]);

        if (!$isGeneral && !empty($this->userIds)) {
            $userNotifications = array_map(function ($userId) use ($notification) {
                return [
                    'notification_id' => $notification->id,
                    'user_id' => $userId,
                ];
            }, $this->userIds);

            UserNotification::insert($userNotifications);
        }
    }
}
