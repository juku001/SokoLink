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
                "name" => "Chakula na Vinywaji",
                "image"=> "categories/fooddrinks.jpg"

            ],
            [
                "icon" => "📱",
                "name" => "Vifaa vya Umeme",
                "image"=> "categories/electronics.jpg"
            ],
            [
                "icon" => "👕",
                "name" => "Mavazi",
                "image" => "categories/clothes.jpg"
            ],
            [
                "icon" => "🏠",
                "name" => "Nyumba na Bustani",
                "image" => "categories/nyumba_bustani.jpeg"
            ],
            [
                "icon" => "🎨",
                "name" => "Sanaa za Mikono",
                "image" => "categories/kazimikono.jpg"
            ],
            [
                "icon" => "🌾",
                "name" => "Mazao ya Kilimo",
                "image"=> "categories/mazao.jpg"
            ]
        ];


        foreach ($categories as $category) {
            Category::create([
                "name" => $category['name'],
                'slug' => Str::slug($category['name']),
                'icon' => $category['icon'],
                "image" => $category['image']
            ]);
        }
    }
}
