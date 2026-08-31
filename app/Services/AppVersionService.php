<?php

namespace App\Services;

use App\Models\AppUpdateSetting;
use App\Traits\ResponseApi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppVersionService
{
    use ResponseApi;

    /**
     * Check mobile application version and update requirements.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        $platform = strtolower(trim((string) $request->query('platform', 'android')));
        $clientVersion = trim((string) $request->query('version', '1.0.0'));

        if (!in_array($platform, ['android', 'ios'])) {
            return $this->error('Invalid platform specified. Allowed values: android, ios', Response::HTTP_BAD_REQUEST);
        }

        $setting = AppUpdateSetting::where('platform', $platform)->first();

        if (!$setting) {
            return $this->success('App version check completed.', Response::HTTP_OK, [
                'update_available' => false,
                'force_update' => false,
                'latest_version' => $clientVersion,
                'min_version' => $clientVersion,
                'update_url' => null,
                'title' => null,
                'message' => null,
            ]);
        }

        $result = $setting->evaluateVersion($clientVersion);

        return $this->success('App version check completed.', Response::HTTP_OK, $result);
    }
}
