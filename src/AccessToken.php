<?php

namespace BilalMardini\FirebaseNotification;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Illuminate\Support\Facades\Cache;

class AccessToken
{
    private static $credentialsFilePath;
    private static $projectId;
    private static $cacheKey = 'firebase_access_token';

    /**
     * Initialize the AccessToken class with the path to your Firebase project's service account json
     * credentials file and the project ID.
     *
     * @param string $credentialsFilePath The path to your Firebase project's service account json
     *                                    credentials file.
     * @param string $projectId The ID of your Firebase project.
     */
    public static function initialize($credentialsFilePath, $projectId)
    {
        self::$credentialsFilePath = $credentialsFilePath;
        self::$projectId = $projectId;
    }

    /**
     * Get a Firebase Cloud Messaging access token.
     *
     * @throws \Exception If AccessToken has not been initialized.
     * @throws \Exception If there is an error while fetching the access token.
     *
     * @return string|null The access token or null if it could not be fetched.
     */
    public static function getToken()
    {
        if (!self::$credentialsFilePath || !self::$projectId) {
            throw new \Exception("AccessToken not initialized. Call initialize() first.");
        }

        $cachedToken = Cache::get(self::$cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        $scope = 'https://www.googleapis.com/auth/firebase.messaging';
        $credentials = new ServiceAccountCredentials($scope, self::$credentialsFilePath);

        try {
            $token = $credentials->fetchAuthToken(HttpHandlerFactory::build());
            $accessToken = $token['access_token'] ?? null;

            if ($accessToken) {
                Cache::put(self::$cacheKey, $accessToken, now()->addMinutes(60));
            }

            return $accessToken;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch access token: " . $e->getMessage());
        }
    }
}
