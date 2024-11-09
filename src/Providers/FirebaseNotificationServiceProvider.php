<?php

namespace BilalMardini\FirebaseNotification\Providers;

use Illuminate\Support\ServiceProvider;
use BilalMardini\FirebaseNotification\AccessToken;
use BilalMardini\FirebaseNotification\FirebaseNotification;

class FirebaseNotificationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/firebase.php', 'firebase'
        );

        $this->app->singleton('firebase-notification', function ($app) {
            $projectId = config('firebase.project_id');
            $credentialsFilePath = config('firebase.credentials_file_path');

            AccessToken::initialize($credentialsFilePath, $projectId);

            return new FirebaseNotification($projectId, $credentialsFilePath);
        });
    }


    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/firebase.php' => config_path('firebase.php'),
        ], 'config');
    }
    
}

