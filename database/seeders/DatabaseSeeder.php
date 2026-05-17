<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name'     => 'Admin DonasiKu',
            'email'    => 'admin@donasiku.id',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        // Sample user
        $user = User::create([
            'name'     => 'Budi Santoso',
            'email'    => 'budi@example.com',
            'password' => Hash::make('password'),
        ]);

        // Sample campaigns
        $campaigns = [
            [
                'title'         => 'Bantu Korban Banjir Sulawesi',
                'description'   => 'Ribuan warga terdampak banjir bandang di Sulawesi membutuhkan bantuan segera. Donasi kamu sangat berarti.',
                'category'      => 'bencana',
                'target_amount' => 50_000_000,
                'collected_amount' => 23_500_000,
                'donor_count'   => 142,
                'image'         => 'https://picsum.photos/seed/banjir/800/500',
                'is_featured'   => true,
                'deadline'      => now()->addDays(30)->format('Y-m-d'),
            ],
            [
                'title'         => 'Beasiswa Anak Yatim Pesisir',
                'description'   => 'Bantu 50 anak yatim di daerah pesisir mendapatkan pendidikan layak hingga SMA.',
                'category'      => 'pendidikan',
                'target_amount' => 30_000_000,
                'collected_amount' => 18_200_000,
                'donor_count'   => 89,
                'image'         => 'https://picsum.photos/seed/beasiswa/800/500',
                'is_featured'   => true,
                'deadline'      => now()->addDays(60)->format('Y-m-d'),
            ],
            [
                'title'         => 'Renovasi Masjid Al-Ikhlas',
                'description'   => 'Masjid berusia 40 tahun ini membutuhkan renovasi menyeluruh. Yuk bantu bersama!',
                'category'      => 'keagamaan',
                'target_amount' => 80_000_000,
                'collected_amount' => 45_000_000,
                'donor_count'   => 210,
                'image'         => 'https://picsum.photos/seed/masjid/800/500',
                'is_featured'   => false,
                'deadline'      => now()->addDays(90)->format('Y-m-d'),
            ],
        ];

        foreach ($campaigns as $c) {
            Campaign::create([...$c, 'user_id' => $admin->id]);
        }
    }
}
