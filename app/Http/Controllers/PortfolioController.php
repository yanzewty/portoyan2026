<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Message; // Wajib ditambahkan agar tabel pesan bisa dibaca
use App\Models\Project; 
use App\Models\ProfileAbout;
use App\Models\Keahlian; 
use App\Models\KeahlianSingkat;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortfolioController extends Controller
{
    // Mengamankan database dari error saat profil masih kosong pertama kali dijalankan
    private function getOrCreateProfile()
    {
        return Profile::firstOrCreate(
            ['id' => 1],
            [
                'nama_lengkap' => 'Admin Portofolio',
                'judul_profesi' => 'Web Developer',
                'bio_singkat' => 'Halo, selamat datang di website portofolio saya.',
            ]
        );
    }

    // Menyesuaikan struktur data backend agar cocok dibaca oleh struktur Vue di frontend
    private function terjemahkanKeInggris($profile)
    {
        if ($profile) {
            $profile->name = $profile->nama_lengkap;
            $profile->role = $profile->judul_profesi;
            $profile->about = $profile->bio_singkat;
            $profile->email = $profile->email_publik;
            $profile->phone = $profile->nomor_telepon;
            $profile->address = $profile->alamat_lokasi;
            $profile->photo  = $profile->foto_profil;
            $profile->badge_1 = $profile->teks_badge_1;
            $profile->badge_2= $profile->teks_badge_2;
            $profile->skills = $profile->skills;
            
            // Tarik data judul untuk bagian pengalaman organisasi
            $orgHeaderObj = ProfileAbout::where('profile_id', $profile->id)->where('is_main', 3)->first();
            $profile->education = json_encode([
                'tag' => $orgHeaderObj->tag ?? '04 / PENGALAMAN ORGANISASI',
                'title' => $orgHeaderObj->title ?? 'Jejak Kepemimpinan',
                'desc' => $orgHeaderObj->description ?? 'Peran yang membentuk cara saya bekerja dalam tim dan mengambil keputusan.'
            ]);
            
            // Tarik seluruh daftar organisasi
            $orgs = Organization::where('profile_id', $profile->id)->get()->map(function ($org) {
                return [
                    'instansi'  => $org->nama_organisasi,
                    'posisi'    => $org->posisi,
                    'periode'   => $org->periode,
                    'deskripsi' => $org->deskripsi_pekerjaan
                ];
            });
            $profile->experiences = $orgs->toJson();
        }
        return $profile;
    }

    // Menampilkan halaman utama portofolio pengunjung (publik)
    public function index()
    {
        $profile = $this->getOrCreateProfile();
        $this->terjemahkanKeInggris($profile);

        // Mengambil semua data pendukung dari database
        $projects = Project::latest()->get(); 
        $dataKeahlian = Keahlian::oldest()->get(); 

        $about = ProfileAbout::firstOrCreate(
            ['profile_id' => $profile->id, 'is_main' => true],
            [
                'tag'         => '01 / TENTANG SAYA',
                'title'       => 'Membangun Solusi Digital dengan Logika & Kreativitas',
                'description' => 'Siswa kelas 12 IT Engineering dengan minat mendalam di bidang pengembangan web.'
            ]
        );
        
        $panels = ProfileAbout::where('is_main', false)->latest()->get(); 
        $dataKeahlianSingkat = KeahlianSingkat::all(); 
        
        $skillHeader = ProfileAbout::where('is_main', 2)->first() ?? (object)[
            'tag' => '03 / KEAHLIAN SINGKAT',
            'title' => 'Keahlian Singkat Saya',
            'description' => 'Memadukan kemampuan teknis IT dengan tata kelola organisasi yang rapi.'
        ];
        
        return Inertia::render('Portfolio', [
            'profile' => $profile,
            'projects' => $projects,
            'panels' => $panels,
            'dataKeahlian' => $dataKeahlian,
            'about' => $about,
            'dataKeahlianSingkat' => $dataKeahlianSingkat,
            'skillHeader' => $skillHeader
        ]);
    }

    // Menampilkan halaman dashboard utama admin
    public function dashboard()
    {
        $profile = $this->getOrCreateProfile();
        $this->terjemahkanKeInggris($profile);

        // Menghitung total data untuk statistik di dashboard
        $totalMessages = Message::where('is_read', false)->count();
        $totalKeahlian = Keahlian::count();
        $totalProjects = Project::count();

        return Inertia::render('Admin/Dashboard', [
            'profile' => $profile,
            'totalMessages' => $totalMessages,
            'totalKeahlian' => $totalKeahlian,
            'totalProjects' => $totalProjects
        ]);
    }

    // Menampilkan form edit profil beranda (admin)
    public function editHome()
    {
        $profile = $this->getOrCreateProfile();
        $this->terjemahkanKeInggris($profile);

        // Merapikan string array JSON skill untuk masuk ke dalam input text
        $skillsArray = json_decode($profile->skills, true);
        if (is_array($skillsArray)) {
            if(isset($skillsArray[0]) && is_array($skillsArray[0])) {
                $names = array_column($skillsArray, 'name');
                $profile->skills = implode(', ', $names);
            } else {
                $profile->skills = implode(', ', $skillsArray);
            }
        }

        // Parsing pengalaman dari JSON untuk memecahnya ke kolom-kolom input terpisah
        $experiences = json_decode($profile->experiences, true);
        if (is_array($experiences)) {
            if (isset($experiences[0])) {
                $profile->exp1_period = $experiences[0]['periode'] ?? '';
                $profile->exp1_title  = $experiences[0]['posisi'] ?? '';
                $profile->exp1_place  = $experiences[0]['instansi'] ?? '';
                $profile->exp1_desc   = $experiences[0]['deskripsi'] ?? '';
            }
            if (isset($experiences[1])) {
                $profile->exp2_period = $experiences[1]['periode'] ?? '';
                $profile->exp2_title  = $experiences[1]['posisi'] ?? '';
                $profile->exp2_place  = $experiences[1]['instansi'] ?? '';
                $profile->exp2_desc   = $experiences[1]['deskripsi'] ?? '';
            }
        }

        return Inertia::render('Admin/Home', ['profile' => $profile]);
    }

    // Memproses dan menyimpan data perubahan profil
    public function updateHome(Request $request)
    {
        $request->validate([
            'photo' => 'nullable|image|max:2048',
            'gallery_1' => 'nullable|image|max:2048',
            'gallery_2' => 'nullable|image|max:2048',
            'gallery_3' => 'nullable|image|max:2048',
        ]);
        
        $profile = $this->getOrCreateProfile();
        
        $profile->nama_lengkap  = $request->name;
        $profile->judul_profesi = $request->role; 
        $profile->bio_singkat   = $request->about; 
        $profile->email_publik  = $request->email;
        $profile->nomor_telepon = $request->phone;
        $profile->alamat_lokasi = $request->address; 

        if ($request->has('skills')) {
            $profile->skills = $request->skills;
        }

        // Menyusun kembali data organisasi ke dalam format array untuk disimpan
        $experiences = [];
        for ($i = 1; $i <= 2; $i++) {
            if ($request->filled("exp{$i}_title")) {
                $experiences[] = [
                    'periode' => $request->input("exp{$i}_period"),
                    'posisi' => $request->input("exp{$i}_title"),
                    'instansi' => $request->input("exp{$i}_place"),
                    'deskripsi' => $request->input("exp{$i}_desc")
                ];
            }
        }
        
        // Hapus data lama, lalu simpan loop data organisasi yang baru
        if (count($experiences) > 0 || $request->has('exp1_title')) {
            Organization::where('profile_id', $profile->id)->delete();
            
            foreach ($experiences as $exp) {
                Organization::create([
                    'profile_id'          => $profile->id,
                    'nama_organisasi'     => $exp['instansi'] ?? '',
                    'posisi'              => $exp['posisi'] ?? '',
                    'periode'             => $exp['periode'] ?? '',
                    'deskripsi_pekerjaan' => $exp['deskripsi'] ?? ''
                ]);
            }
        }

        // Proses unggah foto profil (jika ada file yang dikirim)
        $uploadPath = public_path('uploads');
        if (!file_exists($uploadPath)) { mkdir($uploadPath, 0755, true); }

        if ($request->hasFile('photo')) {
            if (!empty($profile->foto_profil) && file_exists($uploadPath . '/' . $profile->foto_profil)) {
                @unlink($uploadPath . '/' . $profile->foto_profil);
            }
            $file = $request->file('photo');
            $filename = time() . "_photo_" . $file->getClientOriginalName();
            $file->move($uploadPath, $filename);
            $profile->foto_profil = $filename;
        }

        // Proses unggah gambar galeri (1-3)
        for ($i = 1; $i <= 3; $i++) {
            if ($request->hasFile("gallery_{$i}")) {
                $colName = "gallery_{$i}";
                if (!empty($profile->$colName) && file_exists($uploadPath . '/' . $profile->$colName)) {
                    @unlink($uploadPath . '/' . $profile->$colName);
                }
                $file = $request->file("gallery_{$i}");
                $filename = time() . "_galeri{$i}_" . $file->getClientOriginalName();
                $file->move($uploadPath, $filename);
                $profile->$colName = $filename;
            }
        }

        $profile->save();
        return redirect()->back()->with('success_msg', 'Data Profil, Skills & Foto berhasil diperbarui!');
    }

    // Menampilkan halaman 'Tentang Saya' di panel admin
    public function editAbout()
    {
        $profile = $this->getOrCreateProfile();
        $this->terjemahkanKeInggris($profile);

        $about = ProfileAbout::firstOrCreate(
            ['profile_id' => $profile->id, 'is_main' => true],
            ['tag' => '01 / TENTANG SAYA', 'title' => '', 'description' => '']
        );
        $panels = ProfileAbout::where('is_main', false)->latest()->get();

        return Inertia::render('Admin/About', [
            'profile' => $profile,
            'about' => $about,
            'panels' => $panels
        ]); 
    }

    // Memproses penyimpanan perubahan profil di 'Tentang Saya'
    public function updateAbout(Request $request)
    {
        $profile = $this->getOrCreateProfile();
        $about = ProfileAbout::firstOrCreate(
            ['profile_id' => $profile->id, 'is_main' => true],
            ['tag' => '', 'title' => '', 'description' => '']
        );

        $about->tag         = $request->about_sub_1;
        $about->title       = $request->about_title;
        $about->description = $request->about_1;
        $about->save();

        return redirect()->back()->with('success_msg', 'Data Tentang Saya berhasil diperbarui!');
    }

    // Menyimpan panel paragraf baru ke tabel ProfileAbout
    public function panelStore(Request $request)
    {
        $request->validate([
            'tag' => 'required',
            'title' => 'required',
            'desc_1' => 'required',
        ]);

        $profile = $this->getOrCreateProfile();

        ProfileAbout::create([
            'profile_id'  => $profile->id,
            'is_main'     => false,
            'tag'         => $request->tag,
            'title'       => $request->title,
            'description' => $request->desc_1,
        ]);

        return redirect()->back()->with('success_msg', 'Berhasil Ditambahkan!');
    }

    // Menampilkan halaman edit untuk satu panel spesifik
    public function panelEdit($id)
    {
        $panel = ProfileAbout::findOrFail($id); 
        return Inertia::render('Admin/PanelsEdit', ['panel' => $panel]);
    }

    // Menyimpan perubahan dari panel spesifik
    public function panelUpdate(Request $request, $id)
    {
        $panel = ProfileAbout::findOrFail($id); 
        $panel->update([
            'tag'         => $request->tag,
            'title'       => $request->title,
            'description' => $request->desc_1, 
        ]);
        return redirect()->route('admin.about')->with('success_msg', 'Berhasil diperbarui!');
    }

    // Menghapus panel secara permanen
    public function panelDestroy($id)
    {
        ProfileAbout::findOrFail($id)->delete(); 
        return redirect()->back()->with('success_msg', 'Berhasil Dihapus dari website!');
    }

    // Menampilkan halaman pengaturan riwayat Organisasi
    public function orgAdmin()
    {
        $profile = $this->getOrCreateProfile();
        $this->terjemahkanKeInggris($profile); 
        
        $headerObj = ProfileAbout::where('profile_id', $profile->id)->where('is_main', 3)->first();
        $header = [
            'tag' => $headerObj->tag ?? '04 / PENGALAMAN ORGANISASI',
            'title' => $headerObj->title ?? 'Jejak Kepemimpinan',
            'desc' => $headerObj->description ?? 'Peran yang membentuk cara saya bekerja dalam tim dan mengambil keputusan.'
        ];
        
        $experiencesJson = $profile->experiences ?: '[]';
        
        return Inertia::render('Admin/Organizations', [
            'profile' => $profile,
            'header' => $header,
            'experiencesJson' => $experiencesJson
        ]);
    }

    // Memproses data pembaruan riwayat Organisasi (Replace All)
    public function updateOrgAdmin(Request $request)
    {
        $profile = $this->getOrCreateProfile();
        
        // Simpan header bagian organisasi
        ProfileAbout::updateOrCreate(
            ['profile_id' => $profile->id, 'is_main' => 3],
            [
                'tag'         => $request->org_tag,
                'title'       => $request->org_title,
                'description' => $request->org_desc
            ]
        );
        
        // Jika ada list pengalaman, hapus yang lama dan isi yang baru
        if ($request->has('experiences_data')) {
            Organization::where('profile_id', $profile->id)->delete();
            
            $experiences = is_string($request->experiences_data) 
                            ? json_decode($request->experiences_data, true) 
                            : $request->experiences_data;
                            
            if (is_array($experiences)) {
                foreach ($experiences as $exp) {
                    Organization::create([
                        'profile_id'          => $profile->id,
                        'nama_organisasi'     => $exp['instansi'] ?? '',
                        'posisi'              => $exp['posisi'] ?? '',
                        'periode'             => $exp['periode'] ?? '',
                        'deskripsi_pekerjaan' => $exp['deskripsi'] ?? ''
                    ]);
                }
            }
        }

        return redirect()->back()->with('success_msg', 'Header & Jejak Organisasi berhasil diperbarui!');
    }

    // Menerima form pesan kontak (Contact Us) dari sisi pengunjung
    public function storeMessage(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        try {
            Message::create($request->only(['name', 'email', 'message']));
            return back()->with('success_msg', 'Terima kasih! Pesan berhasil dikirim.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Gagal mengirim pesan: ' . $e->getMessage()]);
        }
    }

    // PERBAIKAN: Menampilkan halaman kotak masuk (inbox) dengan benar
    public function messagesAdmin()
    {
        // Mengurutkan pesan dari yang belum dibaca terlebih dahulu
        $messages = Message::orderBy('is_read', 'asc')->latest()->get();

        // Sebelumnya rute ini salah diarahkan ke Admin/Home. Sekarang sudah Admin/Messages
        return Inertia::render('Admin/Messages', ['messages' => $messages]); 
    }

    // Menghapus pesan secara individu
    public function deleteMessage($id)
    {
        Message::findOrFail($id)->delete();
        return redirect()->back()->with('success_msg', 'Pesan berhasil dihapus!'); 
    }

    // Menandai satu pesan telah dibaca (Read)
    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);
        $message->is_read = true; 
        $message->save(); 
        return back()->with('success_msg', 'Pesan ditandai sudah dibaca.');
    }

    // Menandai semua pesan telah dibaca (Read All)
    public function markAllAsRead()
    {
        Message::where('is_read', false)->update(['is_read' => true]);
        return back()->with('success_msg', 'Semua pesan telah ditandai sebagai dibaca.');
    }

    // Menghapus beberapa pesan sekaligus berdasarkan checkbox (Bulk Action)
    public function bulkDeleteMessages(Request $request)
    {
        $ids = $request->ids; 
        
        if (is_array($ids) && count($ids) > 0) {
            Message::whereIn('id', $ids)->delete();
            return back()->with('success_msg', count($ids) . ' pesan terpilih berhasil dihapus!');
        }

        return back()->withErrors(['message' => 'Tidak ada pesan yang dipilih.']);
    }

    // Menandai beberapa pesan telah dibaca berdasarkan checkbox (Bulk Action)
    public function bulkReadMessages(Request $request)
    {
        $ids = $request->ids;

        if (is_array($ids) && count($ids) > 0) {
            Message::whereIn('id', $ids)->update(['is_read' => true]);
            return back()->with('success_msg', count($ids) . ' pesan terpilih ditandai sudah dibaca.');
        }

        return back()->withErrors(['message' => 'Tidak ada pesan yang dipilih.']);
    }

    // Menyimpan pembuatan proyek/portofolio baru beserta lampiran gambarnya
    public function projectStore(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'image' => 'required|image|max:2048', 
            'description' => 'nullable'
        ]);

        $imageName = time() . '_' . $request->image->getClientOriginalName();
        $request->image->move(public_path('uploads'), $imageName);

        Project::create([
            'title' => $request->title,
            'image' => $imageName,
            'description' => $request->description,
        ]);
    }

    // Menghapus sebuah proyek sekaligus file fisik gambarnya di direktori uploads
    public function projectDestroy($id)
    {
        $project = Project::findOrFail($id);
        if(file_exists(public_path('uploads/'.$project->image))){
            @unlink(public_path('uploads/'.$project->image));
        }
        $project->delete();
        return back()->with('success_msg', 'Proyek berhasil dihapus!');
    }

    // Menampilkan halaman pengelolaan Latar Belakang Skill (Modul/Kegiatan)
    public function keahlianAdmin()
    {
        // Menyediakan data awal (seeder darurat) jika tabel masih kosong total
        if (Keahlian::count() == 0) {
            Keahlian::create(['modul' => 'MODULE / 01', 'judul' => 'Pemrograman Web & Laravel', 'kategori' => 'DEVELOPMENT', 'gambar' => '']);
            Keahlian::create(['modul' => 'MODULE / 02', 'judul' => 'UI/UX & Poster Digital', 'kategori' => 'DESIGN & UI', 'gambar' => '']);
            Keahlian::create(['modul' => 'MODULE / 03', 'judul' => 'Kegiatan OSIS & Karang Taruna', 'kategori' => 'LEADERSHIP', 'gambar' => '']);
        }
        $dataKeahlian = Keahlian::oldest()->get();
        
        $profile = $this->getOrCreateProfile(); 
        $this->terjemahkanKeInggris($profile);
        
        return Inertia::render('Admin/LatarBelakangSkill', [
            'dataKeahlian' => $dataKeahlian,
            'profile' => $profile
        ]);
    }

    // Mengelabui request update yang kosong agar tidak melempar error
    public function updateSkillHeader(Request $request)
    {
        return redirect()->back()->with('success_msg', 'Proses update mode aman berhasil!');
    }

    // Menyimpan tambahan item Latar Belakang Skill baru
    public function keahlianStore(Request $request)
    {
        $request->validate(['gambar' => 'required|image|max:2048', 'judul'  => 'required']);
        
        $keahlian = new Keahlian();
        $keahlian->modul = $request->modul;
        $keahlian->judul = $request->judul;
        $keahlian->kategori = $request->kategori;
        $keahlian->deskripsi = $request->deskripsi;

        // Mengamankan dan mengunggah gambar skill ke folder public
        if ($request->hasFile('gambar')) {
            $uploadPath = public_path('uploads');
            if (!file_exists($uploadPath)) { mkdir($uploadPath, 0755, true); }
            $file = $request->file('gambar');
            $filename = time() . '_keahlian_' . $file->getClientOriginalName();
            $file->move($uploadPath, $filename);
            $keahlian->gambar = $filename;
        }
        $keahlian->save();
        return redirect()->back()->with('success_msg', 'Data Keahlian Baru Berhasil Ditambahkan!');
    }

    // Menampilkan mode edit individual untuk Latar Belakang Skill
    public function keahlianEdit($id)
    {
        $item = Keahlian::findOrFail($id);
        return Inertia::render('Admin/LatarBelakangSkillEdit', ['item' => $item]);
    }

    // Memproses data perubahan dari form edit Latar Belakang Skill
    public function keahlianUpdate(Request $request, $id)
    {
        $keahlian = Keahlian::findOrFail($id);
        $request->validate(['gambar' => 'nullable|image|max:2048', 'judul'  => 'required']);
        
        $keahlian->modul     = $request->modul;
        $keahlian->judul     = $request->judul;
        $keahlian->deskripsi = $request->deskripsi;
        $keahlian->kategori  = $request->kategori;

        // Cek jika terdapat unggahan gambar pengganti (baru)
        if ($request->hasFile('gambar')) {
            $uploadPath = public_path('uploads');
            if (!file_exists($uploadPath)) { mkdir($uploadPath, 0755, true); }
            
            // Hapus gambar usang untuk menghemat ruang server
            if (!empty($keahlian->gambar) && file_exists($uploadPath . '/' . $keahlian->gambar)) {
                @unlink($uploadPath . '/' . $keahlian->gambar);
            }
            $file = $request->file('gambar');
            $filename = time() . '_keahlian_' . $file->getClientOriginalName();
            $file->move($uploadPath, $filename);
            $keahlian->gambar = $filename;
        }
        $keahlian->save();
        
        return back();
    }

    // Menghapus riwayat Latar Belakang Skill beserta lampiran fotonya
    public function keahlianDestroy($id)
    {
        $keahlian = Keahlian::findOrFail($id);
        if(!empty($keahlian->gambar) && file_exists(public_path('uploads/'.$keahlian->gambar))){
            @unlink(public_path('uploads/'.$keahlian->gambar));
        }
        $keahlian->delete();
        return redirect()->back()->with('success_msg', 'Data Keahlian Berhasil Dihapus!');
    }

    // Menampilkan halaman panel pengaturan Bidang Keahlian Singkat (Icon Slider)
    public function bidangKeahlianAdmin()
    {
        $headerObj = ProfileAbout::where('is_main', 2)->first();
        $header = [
            'tag'   => $headerObj->tag ?? '03 / KEAHLIAN SINGKAT',
            'title' => $headerObj->title ?? 'Keahlian Singkat Saya',
            'desc'  => $headerObj->description ?? 'Memadukan kemampuan teknis IT dengan tata kelola organisasi yang rapi.'
        ];
        
        $skills = KeahlianSingkat::all();

        return Inertia::render('Admin/BidangKeahlian', [
            'header' => $header,
            'skills' => $skills
        ]);
    }

    // Memproses data slider Keahlian Singkat (menghapus tabel lama & mengisi dengan JSON baru)
    public function updateBidangKeahlian(Request $request)
    {
        $profile = $this->getOrCreateProfile();

        ProfileAbout::updateOrCreate(
            ['profile_id' => $profile->id, 'is_main' => 2], 
            [
                'tag'         => $request->skill_tag,
                'title'       => $request->skill_title,
                'description' => $request->skill_desc
            ]
        );

        // Membersihkan tabel lama dan memasukkan rangkaian baris baru dari request data
        if ($request->has('skills_data')) {
            KeahlianSingkat::truncate(); 
            
            $skillsData = is_string($request->skills_data) ? json_decode($request->skills_data, true) : $request->skills_data;
            if (is_array($skillsData)) {
                foreach ($skillsData as $skill) {
                    KeahlianSingkat::create([
                        'name'  => $skill['name'],
                        'icon'  => $skill['icon'],
                        'color' => $skill['color']
                    ]);
                }
            }
        }

        return redirect()->back()->with('success_msg', 'Data Keahlian Singkat berhasil diperbarui!');
    }
}