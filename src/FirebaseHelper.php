<?php
namespace BilalMardini\FirebaseNotification;

use Illuminate\Support\Facades\Http;
use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\User;

class FirebaseNotification
{
    private string $titleEn;
    private ?string $titleAr = null;
    private string $bodyEn;
    private ?string $bodyAr = null;
    private ?string $icon = null;
    private ?string $topic = null;
    private ?array $users = null;

    public function setTitle(string $titleEn, ?string $titleAr = null): self
    {
        $this->titleEn = $titleEn;
        $this->titleAr = $titleAr;
        return $this;
    }

    public function setBody(string $bodyEn, ?string $bodyAr = null): self
    {
        $this->bodyEn = $bodyEn;
        $this->bodyAr = $bodyAr;
        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function setTopic(string $topic): self
    {
        $this->topic = $topic;
        return $this;
    }

    public function setUsers(array $users): self
    {
        $this->users = $users;
        return $this;
    }

    private function buildPayload(): array
    {
        $message = [
            'data' => [
                'title_en' => $this->titleEn,
                'title_ar' => $this->titleAr ?? '',
                'body_en' => $this->bodyEn,
                'body_ar' => $this->bodyAr ?? '',
            ],
            'android' => [
                'notification' => ['image' => $this->icon ?? '']
            ],
            'apns' => [
                'payload' => ['aps' => ['mutable-content' => 1]],
                'fcm_options' => ['image' => $this->icon ?? '']
            ],
        ];

        if ($this->topic) {
            $message['topic'] = $this->topic;
        } else {
            $tokens = $this->extractUserTokens();
            $message['token'] = $tokens;
        }

        return ['message' => $message];
    }

    private function send(array $payload): int
    {
        $apiUrl = 'https://fcm.googleapis.com/v1/projects/' . config('firebase.project_id') . '/messages:send';
        $accessToken = AccessToken::getToken();

        return Http::withHeaders([
            "Authorization" => "Bearer $accessToken",
            'Content-Type' => 'application/json',
        ])->post($apiUrl, $payload)->status();
    }

    public function push(): bool
    {
        AccessToken::initialize(
            config('firebase.credentials_file_path'),
            config('firebase.project_id')
        );

        $payloads = [$this->buildPayload()];

        $responseCodes = array_map(fn($payload) => $this->send($payload), $payloads);

        if (in_array(200, $responseCodes)) {
            $this->saveNotification($this->topic !== null);
            return true;
        }

        return false;
    }

    private function extractUserTokens(): array
    {
        if (!$this->users) {
            return [];
        }

        return array_filter(array_map(fn($user) => $user->device_token, $this->users));
    }

    private function saveNotification(bool $isGeneral): void
    {
        $notification = Notification::create([
            'title_en' => $this->titleEn,
            'title_ar' => $this->titleAr,
            'description_en' => $this->bodyEn,
            'description_ar' => $this->bodyAr,
            'is_general' => $isGeneral,
        ]);

        if (!$isGeneral && $this->users) {
            UserNotification::insert(array_map(
                fn($user) => ['notification_id' => $notification->id, 'user_id' => $user->id],
                $this->users
            ));
        }
    }
}
