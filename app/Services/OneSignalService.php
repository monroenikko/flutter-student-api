<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OneSignalService
{
    /**
     * Send push notification via OneSignal REST API.
     *
     * @param array $fields
     * @param string $message
     * @return array|null
     */
    public static function sendPush(array $fields, string $message = ''): ?array
    {
        try {
            $url = config('one-signal.url', 'https://onesignal.com/api/v1/');
            $appId = config('one-signal.app_id');
            $authorize = config('one-signal.authorize');
            $mutableContent = config('one-signal.mutable_content', true);

            if (empty($appId) || empty($authorize)) {
                Log::warning('OneSignal push skipped: ONE_SIGNAL_APP_ID or ONE_SIGNAL_AUTHORIZE is not set.');
                return null;
            }

            if (!empty($message) && empty($fields['contents'])) {
                $fields['contents'] = ['en' => $message];
            }

            $fields['app_id'] = $fields['app_id'] ?? $appId;
            $fields['mutable_content'] = $fields['mutable_content'] ?? $mutableContent;

            $endpoint = rtrim($url, '/') . '/notifications';

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Basic ' . $authorize,
                    'Content-Type' => 'application/json; charset=utf-8',
                ])
                ->post($endpoint, $fields);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('OneSignal push notification request failed: ' . $response->body());
            return $response->json();
        } catch (Throwable $e) {
            Log::warning('OneSignal push notification exception: ' . $e->getMessage());
            return null;
        }
    }
}
