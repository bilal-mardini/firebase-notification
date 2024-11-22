# Laravel Firebase Notification

![Package Image](https://github.com/user-attachments/assets/7a4cece0-f86a-4b40-8a6f-5f1ec533edae)

[![Packagist Downloads](https://img.shields.io/packagist/dt/bilalmardini/firebase-notification)](https://packagist.org/packages/bilalmardini/firebase-notification)
[![Latest Version](https://img.shields.io/packagist/v/bilalmardini/firebase-notification)](https://packagist.org/packages/bilalmardini/firebase-notification)

## Overview

**Laravel Firebase Notification** is a powerful package for integrating **Firebase Cloud Messaging (FCM)** into Laravel applications. It provides an intuitive interface for sending push notifications to users, targeting individuals, groups, or topics for broadcast notifications.

## Features

- **Push Notifications**: Send notifications via Firebase Cloud Messaging (FCM).
- **Topic-Based Notifications**: Broadcast messages to subscribers.
- **User-Specific Notifications**: Send personalized messages.
- **Custom Payloads**: Include icons, titles, bodies, and additional data.
- **Rich Media Support**: Customizable message structure with deep linking.
- **Secure Delivery**: Authentication with Firebase using a service account key.

## Requirements

- **PHP**: 7.4 or higher
- **Laravel**: 8.x or higher
- **Firebase**: A project with Cloud Messaging enabled
- **Credentials**: Firebase Service Account credentials

## Installation

Install the package via Composer:

```bash
composer require bilalmardini/firebase-notification
```

If you are not using Laravel package auto-discovery, add the service provider in `config/app.php`:

```php
'providers' => [
    // Other Service Providers
    BilalMardini\FirebaseNotification\Providers\FirebaseNotificationServiceProvider::class,
],
```

Publish the configuration file to set up your Firebase credentials:

```bash
php artisan vendor:publish --provider="BilalMardini\FirebaseNotification\Providers\FirebaseNotificationServiceProvider"
```



## Configuration

Set your Firebase credentials in the published configuration file (`config/firebase.php`):

```php
return [
    'credentials_file_path' => base_path('firebase.json'),
    'project_id' => 'your-firebase-project-id'
];
```

## Usage

### Sending a Notification

#### 1. Topic-Based Notification (Global News Update)

Send a notification to all users subscribed to a specific topic:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use BilalMardini\FirebaseNotification\Facades\FirebaseNotification;

class NewsNotificationController extends Controller
{
    /**
     * Send a global news update to users subscribed to 'global-news'.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendGlobalNewsUpdate()
    {
        $result = FirebaseNotification::setTitle('Breaking News')
                     ->setBody('A major event just happened. Click to read more.')
                     ->setIcon('https://news-website.com/news-icon.png') 
                     ->setTopic('global-news')  // Target 'global-news' topic
                     ->setData(['news_id' => 5678, 'category' => 'breaking-news'])
                     ->push();

        return $result
            ? response()->json(['message' => 'News notification sent successfully.'], 200)
            : response()->json(['message' => 'Failed to send news notification.'], 500);
    }
}
```

**Key Points:**
- Sends notifications to users subscribed to the `global-news` topic.
- Includes custom data such as `news_id` and `category`.

#### 2. User-Specific Notification (Order Status)

Notify specific users about their order status:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use BilalMardini\FirebaseNotification\Facades\FirebaseNotification;

class PromotionNotificationController extends Controller
{
    /**
     * Notify users about a new promotional offer.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPromotionNotification()
    {
        $eligibleUsers = User::where('is_eligible_for_promo', true)->get();

        $result = FirebaseNotification::setTitle('Exclusive Promotion Just for You!')
                     ->setBody('Unlock your special offer now. Limited time only!')
                     ->setIcon('https://yourstore.com/promo-icon.png') 
                     ->setUsers($eligibleUsers)  // Target specific users
                     ->setData(['promo_code' => 'PROMO2024', 'discount' => '20%'])
                     ->push();

        return $result
            ? response()->json(['message' => 'Promotion notification sent successfully.'], 200)
            : response()->json(['message' => 'Failed to send promotion notification.'], 500);
    }
}
```

**Key Points:**
- Sends notifications to users eligible for a promotional offer (`is_eligible_for_promo`).
- Includes custom data like `promo_code` and `discount`.

## Models

### Notification Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'is_general',
    ];

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }
}
```

### UserNotification Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'user_id',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### UserFcmToken Model
```php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFcmToken extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'fcm_token'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```
### Contact

If you have any questions or need further assistance, you can reach out through the following channels:

- **GitHub Issues**: [Submit an issue](https://github.com/bilal-mardini/firebase-notification/issues) for bug reports, feature requests, or general questions.
- **Email**: For direct inquiries, you can contact us at [bilal.mardini1999@gmail.com](mailto:bilal.mardini1999@gmail.com).

We appreciate your interest in our project and your contributions. Thank you for being a part of the Laravel Firebase Notification community!

