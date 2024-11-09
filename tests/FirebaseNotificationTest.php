<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Models\UserFcmToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use BilalMardini\FirebaseNotification\FirebaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FirebaseNotificationTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        UserFcmToken::factory()->create(['user_id' => $this->user->id, 'fcm_token' => 'fake_token']);
    }

    /** @test */
    public function it_can_set_title_body_and_icon()
    {
        $notification = new FirebaseNotification();

        $notification->setTitle('Sample Title')
            ->setBody('Sample Body')
            ->setIcon('https://example.com/icon.png');

        $this->assertEquals('Sample Title', $notification->title);
        $this->assertEquals('Sample Body', $notification->body);
        $this->assertEquals('https://example.com/icon.png', $notification->icon);
    }

    /** @test */
    public function it_can_set_users_and_extract_tokens()
    {
        $notification = new FirebaseNotification();
        $notification->setUsers(collect([$this->user]));

        $this->assertContains('fake_token', $notification->tokens);
        $this->assertContains($this->user->id, $notification->userIds);
    }

    /** @test */
    public function it_builds_the_correct_payload()
    {
        $notification = new FirebaseNotification();
        $notification->setTitle('Sample Title')
            ->setBody('Sample Body')
            ->setIcon('https://example.com/icon.png');

        $payload = $notification->buildPayload('fake_token');

        $this->assertArrayHasKey('message', $payload);
        $this->assertEquals('fake_token', $payload['message']['token']);
        $this->assertEquals('Sample Title', $payload['message']['data']['title']);
        $this->assertEquals('Sample Body', $payload['message']['data']['body']);
    }

    /** @test */
    public function it_sends_the_notification_to_firebase()
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['message' => 'success'], 200)
        ]);

        $notification = new FirebaseNotification();
        $notification->setTitle('Sample Title')
            ->setBody('Sample Body')
            ->setUsers(collect([$this->user]));

        $payload = $notification->buildPayload('fake_token');
        $result = $notification->send($payload);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_logs_error_if_firebase_notification_fails()
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['error' => 'Unauthorized'], 401)
        ]);

        Log::shouldReceive('error')->once();

        $notification = new FirebaseNotification();
        $notification->setTitle('Sample Title')
            ->setBody('Sample Body')
            ->setUsers(collect([$this->user]));

        $payload = $notification->buildPayload('fake_token');
        $result = $notification->send($payload);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_saves_the_notification_and_user_notifications()
    {
        $notification = new FirebaseNotification();
        $notification->setTitle('Sample Title')
            ->setBody('Sample Body')
            ->setUsers(collect([$this->user]))
            ->push();

        $this->assertDatabaseHas('notifications', [
            'title_en' => 'Sample Title',
            'description_en' => 'Sample Body',
            'is_general' => false,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->user->id,
            'notification_id' => Notification::latest()->first()->id,
        ]);
    }
}
