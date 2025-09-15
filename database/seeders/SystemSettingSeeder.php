<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'maintenance_mode', 'value' => '0'], // 0 = off, 1 = on
            ['key' => 'auto_product_approval', 'value' => '1'], // 0 = no, 1 = yes
            ['key' => 'sms_alerts_enabled', 'value' => '1'], // 0 = off, 1 = on
            ['key' => 'sms_provider_api_key', 'value' => Crypt::encryptString(env('SMARTNOLOGY_API_KEY'))],
            ['key' => 'sms_provider_sender_id', 'value' => Crypt::encryptString(env('SMARTNOLOGY_SENDER_ID'))],
            ['key' => 'email_smtp_host', 'value' => 'smtp.mailtrap.io'],
            ['key' => 'email_smtp_port', 'value' => '2525'],
            ['key' => 'email_smtp_username', 'value' => Crypt::encryptString('your_smtp_username')],
            ['key' => 'email_smtp_password', 'value' => Crypt::encryptString('your_smtp_password')],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
