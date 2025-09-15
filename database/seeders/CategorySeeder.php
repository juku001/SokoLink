<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                "icon" => "🍽️",
                "name" => "Chakula na Vinywaji"

            ],
            [
                "icon" => "📱",
                "name" => "Vifaa vya Umeme"
            ],
            [
                "icon" => "👕",
                "name" => "Mavazi"
            ],
            [
                "icon" => "🏠",
                "name" => "Nyumba na Bustani"
            ],
            [
                "icon" => "🎨",
                "name" => "Sanaa za Mikono"
            ],
            [
                "icon" => "🌾",
                "name" => "Mazao ya Kilimo"
            ]
        ];


        foreach ($categories as $category) {
            Category::create([
                "name" => $category['name'],
                'slug' => Str::slug($category['name']),
                'icon' => $category['icon'],
            ]);
        }
    }
}
