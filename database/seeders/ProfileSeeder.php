<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Profile;
use App\Models\ProfileAbout;
use App\Models\Organization;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        // 1. PROFIL UTAMA (Masuk ke tabel profiles)
        $profile = Profile::updateOrCreate(
            ['id' => 1],
            [
                'nama_lengkap'=> 'Alfiansyah Ibdani',
                'judul_profesi'=> 'IT ENGINEERING & IT Enthusiast',
                'bio_singkat'=> 'Siswa Kelas 12 SMK Negeri 1 Surabaya yang memiliki ketertarikan mendalam pada pengembangan web, jaringan komputer, serta aktif dalam kegiatan organisasi kepemudaan.',
                'nomor_telepon'=> '0882-3592-1495',
                'alamat_lokasi'=> 'Perumahan Palempertiwi, Menganti, Gresik, Jawa Timur',
                'teks_badge_1'=> 'Tersedia untuk Kolaborasi',
                'teks_badge_2'=> 'Web Developer',
            ]
        );

        // 2. HEADER TENTANG & SKILL (Masuk ke tabel profile_abouts)
        ProfileAbout::updateOrCreate(
            ['profile_id' => $profile->id, 'is_main' => 1],
            [
                'tag' => '01 / TENTANG SAYA',
                'title' => 'Membangun Solusi Digital',
                'description' => 'Siswa kelas 12 IT Engineering dengan minat mendalam di bidang pengembangan web.'
            ]
        );

        ProfileAbout::updateOrCreate(
            ['profile_id' => $profile->id, 'is_main' => 2],
            [
                'tag' => 'LATAR BELAKANG & SKILL',
                'title' => 'Dokumentasi Kegiatan IT',
                'description' => 'Dokumentasi kegiatan pemrograman web, desain UI/UX, dan organisasi sosial.'
            ]
        );

        ProfileAbout::updateOrCreate(
            ['profile_id' => $profile->id, 'is_main' => 3],
            [
                'tag' => '04 / PENGALAMAN ORGANISASI',
                'title' => 'Jejak Kepemimpinan',
                'description' => 'Peran yang membentuk cara saya bekerja dalam tim dan mengambil keputusan.'
            ]
        );

        // 3. DATA ORGANISASI (Masuk ke tabel organizations)
        Organization::updateOrCreate(
            ['profile_id' => $profile->id, 'nama_organisasi' => 'OSIS SMK Negeri 1 Surabaya'],
            [
                'periode' => '2025 - Sekarang',
                'posisi' => 'Sekretaris Umum',
                'deskripsi_pekerjaan' => 'Bertanggung jawab penuh atas administrasi organisasi, tata kelola surat-menyurat resmi, serta melakukan koordinasi intensif antar divisi.'
            ]
        );

        Organization::updateOrCreate(
            ['profile_id' => $profile->id, 'nama_organisasi' => 'Warga Setempat'],
            [
                'periode' => '2023 - Sekarang',
                'posisi' => 'Ketua Karang Taruna',
                'deskripsi_pekerjaan' => 'Memimpin tim pemuda dalam merancang dan mengeksekusi kegiatan sosial kemasyarakatan.'
            ]
        );
    }
}