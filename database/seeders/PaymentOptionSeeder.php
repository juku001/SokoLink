<?php

namespace Database\Seeders;

use App\Models\PaymentOptions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Str;

class PaymentOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            [
                'name' => 'Pay Now',
                'description' => 'This is direct payment via Mobile Money, Card or Bank Transfer'
            ],
            [
                'name' => 'Save & Pay Later',
                'description' => 'Reserve order and pay later.'
            ],
            [
                'name' => 'Request Payment',
                'description' => 'Generate Payment Link or QR'
            ]
        ];

        foreach ($options as $option) {
            PaymentOptions::create([
                'name' => $option['name'],
                'description' => $option['description'],
                'key' => Str::slug($option['name']),
            ]);
        }
    }
}
