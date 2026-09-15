<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Elektronik', 'Dompet & Kartu', 'Kunci', 'Pakaian', 'Buku & Dokumen', 'Aksesoris', 'Kartu & Sertifikat', 'Tas & Ransel', 'Botol & Tumbler', 'Lainnya'];

        $locations = ['Perpustakaan', 'Aula', 'Gedung Utama', 'Pusat Kegiatan Mahasiswa', 'Kantin', 'Lapangan Olahraga', 'Area Parkir', 'Asrama', 'Ruang Kuliah A', 'Ruang Kuliah B', 'Laboratorium Komputer', 'Kantor Administrasi', 'Lainnya'];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        foreach ($locations as $name) {
            Location::firstOrCreate(['name' => $name]);
        }

        User::firstOrCreate(
            ['email' => 'admin@campus.ac.id'],
            ['name' => 'Admin Campus', 'role' => 'admin', 'password' => bcrypt('password')]
        );

        User::firstOrCreate(
            ['email' => 'demo@campus.ac.id'],
            ['name' => 'Demo Student', 'role' => 'student', 'password' => bcrypt('password')]
        );
    }
}