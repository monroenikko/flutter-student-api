<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUpdateSetting extends Model
{
    protected $table = 'app_update_settings';

    protected $fillable = [
        'platform',
        'is_enabled',
        'force_update',
        'min_version',
        'latest_version',
        'update_url',
        'update_title',
        'update_message',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'force_update' => 'boolean',
    ];

    /**
     * Check update requirement for a given client version.
     *
     * @param string $clientVersion
     * @return array
     */
    public function evaluateVersion(string $clientVersion): array
    {
        if (!$this->is_enabled) {
            return [
                'update_available' => false,
                'force_update' => false,
                'latest_version' => $this->latest_version,
                'min_version' => $this->min_version,
                'update_url' => $this->update_url,
                'title' => $this->update_title,
                'message' => $this->update_message,
            ];
        }

        // Check if emergency killswitch is active
        if ($this->force_update) {
            $isBelowLatest = version_compare($clientVersion, $this->latest_version, '<');
            return [
                'update_available' => $isBelowLatest,
                'force_update' => $isBelowLatest,
                'latest_version' => $this->latest_version,
                'min_version' => $this->min_version,
                'update_url' => $this->update_url,
                'title' => $this->update_title,
                'message' => $this->update_message,
            ];
        }

        $isBelowMin = version_compare($clientVersion, $this->min_version, '<');
        $isBelowLatest = version_compare($clientVersion, $this->latest_version, '<');

        return [
            'update_available' => $isBelowLatest,
            'force_update' => $isBelowMin,
            'latest_version' => $this->latest_version,
            'min_version' => $this->min_version,
            'update_url' => $this->update_url,
            'title' => $this->update_title,
            'message' => $this->update_message,
        ];
    }
}
