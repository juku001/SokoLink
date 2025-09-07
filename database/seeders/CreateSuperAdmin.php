<?php

namespace Database\Seeders;

use App\Helpers\AuthProviderType;
use App\Helpers\CustomFunctions;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateSuperAdmin extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = 'admin123';
        $data = [
            "name"=> "SokoLink Admin",
            "email"=> "admin@sokolink.store",
            "password"=> password_hash($password, PASSWORD_DEFAULT),
            "email_verified_at"=> now(),
            "phone"=> "+255712345678",
            "phone_verified_at"=> now(),
            "role"=> "super_admin"
        ];

        $user = User::create($data);

        CustomFunctions::createProviders($user->id, AuthProviderType::Email);
    }
}
