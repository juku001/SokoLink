<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $imagePath = '/images/payment_methods';
        $methods = [
            [
                'display' => 'Mix By Yas',
                'type' => 'mno',
                'logo' => $imagePath . '/pay_mix.png',
                'code' => env('PAY_METHOD_TIGO')
            ],
            [
                'display' => 'Airtel',
                'type' => 'mno',
                'logo' => $imagePath . '/pay_airtel.png',
                'code' => env('PAY_METHOD_AIRTEL')
            ],
            [
                'display' => 'Halopesa',
                'type' => 'mno',
                'logo' => $imagePath . '/pay_halopesa.png',
                'code' => env('PAY_METHOD_HALOTEL')
            ],
            [
                'display' => 'M-Pesa',
                'type' => 'mno',
                'logo' => $imagePath . '/pay_voda.png',
                'code' => env('PAY_METHOD_VODA')
            ],
            [
                'display' => 'Mastercard/Visa',
                'type' => 'card',
                'logo' => $imagePath . '/pay_card.png',
                'code' => env('PAY_METHOD_CARD')
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::create([
                'display' => $method['display'],
                'type' => $method['type'],
                'image' => $method['logo'],
                'code' => $method['code']
            ]);
        }
    }
}
