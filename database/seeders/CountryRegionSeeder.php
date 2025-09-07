<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountryRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $country = Country::create([
            'name' => 'Tanzania',
            'abbr' => 'TZ'
        ]);

        $regions = [
            ['name' => 'Arusha', 'postal_code' => '23100', 'country_id' => $country->id],
            ['name' => 'Dar es Salaam', 'postal_code' => '11100', 'country_id' => $country->id],
            ['name' => 'Dodoma', 'postal_code' => '41100', 'country_id' => $country->id],
            ['name' => 'Geita', 'postal_code' => '30100', 'country_id' => $country->id],
            ['name' => 'Iringa', 'postal_code' => '51100', 'country_id' => $country->id],
            ['name' => 'Kagera', 'postal_code' => '35100', 'country_id' => $country->id],
            ['name' => 'Katavi', 'postal_code' => '50100', 'country_id' => $country->id],
            ['name' => 'Kigoma', 'postal_code' => '47100', 'country_id' => $country->id],
            ['name' => 'Kilimanjaro', 'postal_code' => '25100', 'country_id' => $country->id],
            ['name' => 'Lindi', 'postal_code' => '65100', 'country_id' => $country->id],
            ['name' => 'Manyara', 'postal_code' => '27100', 'country_id' => $country->id],
            ['name' => 'Mara', 'postal_code' => '32100', 'country_id' => $country->id],
            ['name' => 'Mbeya', 'postal_code' => '53100', 'country_id' => $country->id],
            ['name' => 'Morogoro', 'postal_code' => '67100', 'country_id' => $country->id],
            ['name' => 'Mtwara', 'postal_code' => '63100', 'country_id' => $country->id],
            ['name' => 'Mwanza', 'postal_code' => '33100', 'country_id' => $country->id],
            ['name' => 'Njombe', 'postal_code' => '59100', 'country_id' => $country->id],
            ['name' => 'Pwani', 'postal_code' => '66100', 'country_id' => $country->id],
            ['name' => 'Rukwa', 'postal_code' => '55100', 'country_id' => $country->id],
            ['name' => 'Ruvuma', 'postal_code' => '57100', 'country_id' => $country->id],
            ['name' => 'Shinyanga', 'postal_code' => '37100', 'country_id' => $country->id],
            ['name' => 'Simiyu', 'postal_code' => '39100', 'country_id' => $country->id],
            ['name' => 'Singida', 'postal_code' => '43100', 'country_id' => $country->id],
            ['name' => 'Songwe', 'postal_code' => '56100', 'country_id' => $country->id],
            ['name' => 'Tabora', 'postal_code' => '45100', 'country_id' => $country->id],
            ['name' => 'Tanga', 'postal_code' => '21100', 'country_id' => $country->id],
            // Zanzibar regions
            ['name' => 'Kaskazini Pemba', 'postal_code' => '72100', 'country_id' => $country->id],
            ['name' => 'Kaskazini Unguja', 'postal_code' => '73100', 'country_id' => $country->id],
            ['name' => 'Kusini Pemba', 'postal_code' => '72200', 'country_id' => $country->id],
            ['name' => 'Kusini Unguja', 'postal_code' => '73200', 'country_id' => $country->id],
            ['name' => 'Mjini Magharibi', 'postal_code' => '73300', 'country_id' => $country->id],
        ];

        $country->regions()->createMany($regions);
    }
}
