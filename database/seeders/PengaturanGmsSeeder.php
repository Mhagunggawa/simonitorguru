<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PengaturanGmsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\PengaturanGms::create([
            'waktu_mulai' => '07:15:00',
            'waktu_selesai' => '08:00:00',
        ]);
    }
}
