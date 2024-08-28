<?php

namespace BilalMardini\FirebaseNotification\Tests;

use BilalMardini\FirebaseNotification\FirebaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Models\UserNotification;

class FirebaseNotificationTest extends TestCase
{
    use RefreshDatabase; 

    protected function setUp(): void
    {
        parent::setUp();
        
    }

    public function test_it_can_send_a_topic_notification()
    {
        $notification = new FirebaseNotification();
        $notification->setTitle('Breaking News!', 'أخبار عاجلة!')
                     ->setBody('A major event has just happened. Stay tuned for updates.', 'حدث كبير للتو. ترقبوا التحديثات.')
                     ->setTopic('news');

        Http::fake([
            '*' => Http::response([], 200)
        ]);

        $result = $notification->push();

        $this->assertTrue($result);

        $this->assertDatabaseHas('notifications', [
            'title_en' => 'Breaking News!',
            'title_ar' => 'أخبار عاجلة!',
            'description_en' => 'A major event has just happened. Stay tuned for updates.',
            'description_ar' => 'حدث كبير للتو. ترقبوا التحديثات.',
            'is_general' => true,
        ]);
    }

    public function test_it_can_send_a_notification_to_specific_users()
    {
        $user = User::factory()->create(['device_token' => 'test_token']); 
        $notification = new FirebaseNotification();
        $notification->setTitle('Reminder', 'تذكير')
                     ->setBody('Don’t forget to complete your profile.', 'لا تنسى إكمال ملفك الشخصي.')
                     ->setUsers([$user]);

        Http::fake([
            '*' => Http::response([], 200)
        ]);

        $result = $notification->push();

    
        $this->assertTrue($result);

        $this->assertDatabaseHas('notifications', [
            'title_en' => 'Reminder',
            'title_ar' => 'تذكير',
            'description_en' => 'Don’t forget to complete your profile.',
            'description_ar' => 'لا تنسى إكمال ملفك الشخصي.',
            'is_general' => false,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
        ]);
    }

    public function test_it_handles_failed_notification()
    {
        $notification = new FirebaseNotification();
        $notification->setTitle('System Alert', 'تنبيه النظام')
                     ->setBody('There was an issue processing your request.', 'حدثت مشكلة في معالجة طلبك.')
                     ->setTopic('alerts');

        Http::fake([
            '*' => Http::response([], 500)
        ]);

        $result = $notification->push();

        $this->assertFalse($result);
    }
}
