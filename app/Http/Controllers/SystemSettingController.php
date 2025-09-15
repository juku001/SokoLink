<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class SystemSettingController extends Controller
{
    protected $settings;

    public function __construct(SystemSetting $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get all system settings
     * GET /settings
     */
    public function index()
    {
        $settings = $this->settings->all(); // cached automatically
        return ResponseHelper::success($settings, "System settings retrieved successfully.");
    }

    /**
     * Update system settings
     * POST /settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'maintenance_mode' => 'nullable|boolean',
            'auto_product_approval' => 'nullable|boolean',
            'sms_alerts_enabled' => 'nullable|boolean',
            'sms_provider_api_key' => 'nullable|string',
            'sms_provider_sender_id' => 'nullable|string',
            'email_smtp_host' => 'nullable|string',
            'email_smtp_port' => 'nullable|integer',
            'email_smtp_username' => 'nullable|string',
            'email_smtp_password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields.", 422);
        }

        $data = $validator->validated();

        foreach ($data as $key => $value) {
            if (in_array($key, ['sms_provider_api_key', 'sms_provider_sender_id', 'email_smtp_username', 'email_smtp_password'])) {
                $value = Crypt::encryptString($value); // encrypt sensitive data
            }
            $this->settings->set($key, $value); // updates DB + cache
        }

        return ResponseHelper::success($this->settings->all(), "System settings updated successfully.");
    }
}
