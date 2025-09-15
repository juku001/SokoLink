<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingService
{
    protected $cacheKey = 'system_settings';
    protected $cacheTtl = 3600; // cache for 1 hour


    
    /**
     * Load settings from DB into cache
     */
    public function loadSettingsToCache(): void
    {
        $settings = SystemSetting::all()->pluck('value', 'key')->toArray();
        Cache::put($this->cacheKey, $settings, $this->cacheTtl);
    }

    /**
     * Get a setting value by key
     */
    public function get(string $key, $decrypt = false)
    {
        $settings = Cache::remember($this->cacheKey, $this->cacheTtl, function () {
            return SystemSetting::all()->pluck('value', 'key')->toArray();
        });

        $value = $settings[$key] ?? null;

        if ($value && $decrypt) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $value;
    }

    /**
     * Update a setting
     */
    public function set(string $key, $value, $encrypt = false)
    {
        if ($encrypt) {
            $value = Crypt::encryptString($value);
        }

        SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Update cache
        $settings = Cache::get($this->cacheKey, []);
        $settings[$key] = $value;
        Cache::put($this->cacheKey, $settings, $this->cacheTtl);

        return true;
    }
}
