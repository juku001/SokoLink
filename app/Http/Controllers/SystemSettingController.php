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

    /**
     * @OA\Get(
     *     path="/system_settings",
     *     summary="Get all system settings",
     *     description="Retrieve all system-wide settings, including maintenance mode, auto product approval, SMS and email configurations",
     *     tags={"Admin"},
     *     @OA\Response(
     *         response=200,
     *         description="System settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="System settings retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="maintenance_mode", type="boolean", example=false),
     *                 @OA\Property(property="auto_product_approval", type="boolean", example=true),
     *                 @OA\Property(property="sms_alerts_enabled", type="boolean", example=true),
     *                 @OA\Property(property="sms_provider_api_key", type="string", example="encrypted_string"),
     *                 @OA\Property(property="sms_provider_sender_id", type="string", example="encrypted_string"),
     *                 @OA\Property(property="email_smtp_host", type="string", example="smtp.mailtrap.io"),
     *                 @OA\Property(property="email_smtp_port", type="integer", example=587),
     *                 @OA\Property(property="email_smtp_username", type="string", example="encrypted_string"),
     *                 @OA\Property(property="email_smtp_password", type="string", example="encrypted_string")
     *             )
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
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

    /**
     * @OA\Post(
     *     path="/system_settings",
     *     summary="Update system settings",
     *     description="Update system settings such as maintenance mode, auto product approval, SMS and email configurations. Sensitive values are encrypted automatically",
     *     tags={"Admin"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="maintenance_mode", type="boolean", example=false),
     *             @OA\Property(property="auto_product_approval", type="boolean", example=true),
     *             @OA\Property(property="sms_alerts_enabled", type="boolean", example=true),
     *             @OA\Property(property="sms_provider_api_key", type="string", example="my_api_key"),
     *             @OA\Property(property="sms_provider_sender_id", type="string", example="SENDER_ID"),
     *             @OA\Property(property="email_smtp_host", type="string", example="smtp.mailtrap.io"),
     *             @OA\Property(property="email_smtp_port", type="integer", example=587),
     *             @OA\Property(property="email_smtp_username", type="string", example="username"),
     *             @OA\Property(property="email_smtp_password", type="string", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="System settings updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="System settings updated successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
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
