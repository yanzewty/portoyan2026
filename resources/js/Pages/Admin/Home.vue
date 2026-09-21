<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

// Menerima data props profil yang dikirim dari backend Laravel
const props = defineProps({
    profile: { type: Object, default: () => ({}) }
});

const page = usePage();

// Path foto default jika profil belum memiliki foto
const defaultPhoto = '/uploads/1786586192_profil_IMG_20260707_112708_146.jpg';

// Inisialisasi data form menggunakan Inertia useForm untuk proses update profil
const form = useForm({
    name: props.profile?.name || 'Alfiansyah Ibdani',
    role: props.profile?.role || 'IT ENGINEERING & IT Enthusiast',
    about: props.profile?.about || 'Siswa Kelas 12 SMK Negeri 1 Surabaya yang memiliki ketertarikan mendalam pada pengembangan web, jaringan komputer, serta aktif dalam kegiatan organisasi kepemudaan.',
    address: props.profile?.address || 'Perumahan Palempertiwi, Menganti, Gresik, Jawa Timur',
    badge_1: props.profile?.badge_1 || 'Tersedia untuk Kolaborasi',
    badge_2: props.profile?.badge_2 || 'Web Developer',
    skills: '',
    email: props.profile?.email || 'yanzewty@gmail.com',
    phone: props.profile?.phone || '0882-3592-1495',
    photo: null,
});

// ==========================================
// DAFTAR KEAHLIAN (TEKS BERJALAN)
// ==========================================
let rowId = 0; // id unik untuk tiap baris agar :key di v-for tidak pernah kembar

// Mengubah data skill dari database (JSON atau teks dipisah koma) menjadi array nama
const parseSkills = (raw) => {
    if (!raw) return ['HTML', 'CSS', 'Laravel'];
    if (Array.isArray(raw)) return raw.map(item => String(item?.name ?? item ?? '').trim()).filter(n => n !== '');

    try {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
            return parsed.map(item => String(item?.name ?? item ?? '').trim()).filter(n => n !== '');
        }
    } catch (e) {}

    return String(raw).split(',').map(s => s.trim()).filter(s => s !== '');
};

// Mengubah array nama menjadi baris input (selalu ada minimal 1 baris kosong agar form tidak hampa)
const toRows = (names) => {
    const rows = names.map(name => ({ id: ++rowId, name }));
    if (rows.length === 0) rows.push({ id: ++rowId, name: '' });
    return rows;
};

const dynamicSkills = ref(toRows(parseSkills(props.profile?.skills)));

const addSkill = () => dynamicSkills.value.push({ id: ++rowId, name: '' });
const removeSkill = (index) => dynamicSkills.value.splice(index, 1);

// ==========================================
// DETEKSI PERUBAHAN (tombol Simpan hanya aktif kalau ada yang berubah)
// ==========================================
// Foto dipisah dari snapshot teks karena berupa File, bukan teks
const currentState = () => JSON.stringify({
    name: form.name,
    role: form.role,
    about: form.about,
    address: form.address,
    badge_1: form.badge_1,
    badge_2: form.badge_2,
    // baris kosong diabaikan karena tidak ikut tersimpan
    skills: dynamicSkills.value.map(s => s.name.trim()).filter(n => n !== ''),
});

// "Titik acuan" = kondisi terakhir yang sudah tersimpan. Batal akan mengembalikan form ke sini.
const savedState = ref(currentState());

const isDirty = computed(() => form.photo !== null || currentState() !== savedState.value);

// ==========================================
// FOTO PROFIL
// ==========================================
const photoInput = ref(null);
const savedPhotoUrl = ref(props.profile?.photo ? `/uploads/${props.profile.photo}` : defaultPhoto);
const photoPreview = ref(savedPhotoUrl.value);

// State untuk notifikasi toast dan modal peringatan
const showSuccessToast = ref(false);
const toastMessage = ref('');
let toastTimer = null;
const modalAlert = ref({ show: false, title: '', message: '' });

// Notif sukses: muncul 3 detik lalu hilang sendiri
const showToast = (message) => {
    toastMessage.value = message;
    showSuccessToast.value = true;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { showSuccessToast.value = false; }, 3000);
};

// Kalau backend mengirim flash 'success_msg', tampilkan lewat toast yang sama (ikut hilang otomatis)
watch(
    () => page.props.flash,
    (flash) => { if (flash?.success_msg) showToast(flash.success_msg); },
    { immediate: true }
);

// Membuang pratinjau sementara (blob) supaya tidak menumpuk di memori
const discardPreviewBlob = () => {
    if (photoPreview.value.startsWith('blob:') && photoPreview.value !== savedPhotoUrl.value) {
        URL.revokeObjectURL(photoPreview.value);
    }
};

// Fungsi untuk menangani validasi dan pratinjau unggahan foto baru
const handlePhotoUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const validImageTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!validImageTypes.includes(file.type)) {
        modalAlert.value = {
            show: true,
            title: 'Format Tidak Valid',
            message: 'Silakan pilih file gambar (JPG, JPEG, PNG).'
        };
        e.target.value = '';
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        modalAlert.value = {
            show: true,
            title: 'File Terlalu Besar',
            message: 'Ukuran foto maksimal 2MB.'
        };
        e.target.value = '';
        return;
    }

    discardPreviewBlob();
    form.photo = file;
    photoPreview.value = URL.createObjectURL(file);
};

// ==========================================
// BATAL & SIMPAN
// ==========================================
// Membatalkan semua perubahan yang belum disimpan, form kembali ke kondisi tersimpan terakhir
const cancelChanges = () => {
    const saved = JSON.parse(savedState.value);
    ['name', 'role', 'about', 'address', 'badge_1', 'badge_2'].forEach(key => { form[key] = saved[key]; });
    dynamicSkills.value = toRows(saved.skills);

    discardPreviewBlob();
    form.photo = null;
    photoPreview.value = savedPhotoUrl.value;
    if (photoInput.value) photoInput.value.value = '';

    form.clearErrors();
};

// Fungsi untuk memformat data skill ke JSON lalu mengirimkan data form ke server
const submit = () => {
    if (!isDirty.value || form.processing) return; // tidak ada yang berubah = tidak perlu kirim

    const skillsArray = dynamicSkills.value
        .filter(s => s.name.trim() !== '')
        .map(s => ({ name: s.name.trim() }));

    form.skills = JSON.stringify(skillsArray);

    form.post('/admin/home/update', {
        preserveScroll: true,
        preserveState: true, // form tidak di-reset paksa, jadi toast & kondisi form tetap terkendali
        onSuccess: () => {
            // Rapikan baris kosong, lalu jadikan kondisi sekarang sebagai titik acuan baru
            dynamicSkills.value = toRows(skillsArray.map(s => s.name));
            savedState.value = currentState();

            form.photo = null;
            savedPhotoUrl.value = photoPreview.value;
            if (photoInput.value) photoInput.value.value = '';

            showToast(page.props.flash?.success_msg || 'Perubahan profil berhasil disimpan.');
        },
        onError: (errors) => {
            modalAlert.value = {
                show: true,
                title: 'Gagal Menyimpan',
                message: Object.values(errors)[0] || 'Terjadi kesalahan saat menyimpan. Coba lagi.'
            };
        }
    });
};
</script>

<template>
    <Head title="Edit Home Profil" />

    <div class="dash-wrapper">
        <main class="dash-main">
            
            <!-- Header Halaman -->
            <header class="page-header">
                <div class="header-content">
                    <Link href="/admin" class="btn-back"><i class='bx bx-arrow-back'></i> Kembali ke Dashboard</Link>
                    <h1>Edit Home Profil</h1>
                    <p>Atur informasi utama yang muncul di halaman paling depan portofoliomu.</p>
                </div>
                <div class="header-action">
                    <a href="/" target="_blank" class="btn-view-site">
                        <i class='bx bx-link-external'></i> Lihat Website
                    </a>
                </div>
            </header>

            <form @submit.prevent="submit" class="dash-form">
                <input type="hidden" v-model="form.email">
                <input type="hidden" v-model="form.phone">

                <div class="form-grid-layout">
                    
                    <div class="form-column">
                        <!-- Kartu Identitas Utama & Foto Profil -->
                        <div class="dash-card">
                            <div class="card-head">
                                <div class="icon-wrap-purple"><i class='bx bx-user'></i></div>
                                <h2>Identitas Utama</h2>
                            </div>
                            <div class="card-body">
                                <div class="avatar-section">
                                    <div class="avatar-ring">
                                        <img :src="photoPreview" alt="Preview">
                                    </div>
                                    <div class="avatar-action">
                                        <button type="button" class="btn-upload" @click="$refs.photoInput.click()">
                                            <i class='bx bx-camera'></i> Unggah Foto
                                        </button>
                                        <input type="file" ref="photoInput" accept="image/png, image/jpeg, image/jpg" style="display:none;" @change="handlePhotoUpload">
                                        <span class="hint">JPG, PNG. Max 2MB.</span>
                                    </div>
                                </div>
                               
                                <div class="input-group">
                                    <label>Nama Lengkap</label>
                                    <input type="text" v-model="form.name" required placeholder="Contoh: Alfiansyah Ibdani">
                                </div>
                                <div class="input-group">
                                    <label>Posisi</label>
                                    <input type="text" v-model="form.role" required placeholder="Contoh: IT ENGINEERING">
                                </div>
                            </div>
                        </div>

                        <!-- Kartu Deskripsi & Bio Profil -->
                        <div class="dash-card">
                            <div class="card-head">
                                <div class="icon-wrap-purple"><i class='bx bx-text'></i></div>
                                <h2>Deskripsi Profil</h2>
                            </div>
                            <div class="card-body">
                                <div class="input-group">
                                    <label>Bio Singkat (Paragraf)</label>
                                    <textarea v-model="form.about" rows="4" required placeholder="Tuliskan bio singkatmu..."></textarea>
                                </div>
                                <div class="input-group">
                                    <label>Lokasi / Alamat (Caption Bawah)</label>
                                    <input type="text" v-model="form.address" required placeholder="Contoh: Surabaya, Jawa Timur">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-column">
                        <!-- Kartu Label / Badge Foto -->
                        <div class="dash-card">
                            <div class="card-head">
                                <div class="icon-wrap-purple"><i class='bx bx-purchase-tag-alt'></i></div>
                                <h2>Label Di foto (Opsional)</h2>
                            </div>
                            <div class="card-body">
               <div class="input-group">
                                    <label>Teks 1 (Kanan Atas)</label>
                          <div class="input-icon-wrap">
                      <i class='bx bx-id-card'></i>
                             <input type="text" v-model="form.badge_1" placeholder="Tersedia Kolaborasi">
                          </div>
                 </div>
           <div class="input-group">
       <label>Teks 2 (Kiri Bawah)</label>
         <div class="input-icon-wrap">
           <i class='bx bx-code-alt'></i>
            <input type="text" v-model="form.badge_2" placeholder="Web Developer">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kartu Keahlian Berjalan -->
         <div class="dash-card">
                  <div class="card-head">
                                <div class="icon-wrap-purple"><i class='bx bx-slider-alt'></i></div>
                 <h2>Teks Berjalan</h2>
                            </div>
                            <div class="card-body">
         <p class="sub-hint">Tambahkan poin poin keahlian yang berjalan di layar utama.</p>
                                <div class="skills-list">
                        <div v-for="(skill, index) in dynamicSkills" :key="skill.id" class="skill-row">
                <input type="text" v-model="skill.name" :placeholder="'Skill ' + (index + 1)">
              <button type="button" class="btn-del" @click="removeSkill(index)" title="Hapus">
               <i class='bx bx-trash'></i>
                    </button>
                 </div>
              </div>
          <button type="button" class="btn-add" @click="addSkill">
               <i class='bx bx-plus'></i> Tambah Item
               </button>
              </div>
            </div>

                        <!-- Tombol Aksi Simpan -->
                        <div class="submit-area">
                            <p class="dirty-note" :class="{ 'is-dirty': isDirty }">
                                <i :class="isDirty ? 'bx bxs-circle' : 'bx bx-check-circle'"></i>
                                {{ isDirty ? 'Ada perubahan yang belum disimpan' : 'Belum ada perubahan' }}
                            </p>
                            <div class="submit-buttons">
                                <button type="button" class="btn-cancel" v-if="isDirty" @click="cancelChanges" :disabled="form.processing">
                                    <i class='bx bx-undo'></i> Batal
                                </button>
                                <button type="submit" class="btn-save" :disabled="!isDirty || form.processing">
                                    <i :class="form.processing ? 'bx bx-loader-alt bx-spin' : 'bx bx-save'"></i>
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Semua Perubahan' }}
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </form>

        </main>

        <!-- Notifikasi Toast Berhasil -->
        <div class="toast" :class="{'toast-show': showSuccessToast}">
            <div class="toast-icon"><i class='bx bx-check'></i></div>
            <div class="toast-text">{{ toastMessage }}</div>
        </div>

        <!-- Modal Peringatan Validasi -->
        <div class="modal-backdrop" :class="{'modal-show': modalAlert.show}" @click="modalAlert.show = false">
            <div class="modal-box" @click.stop>
                <div class="modal-icon"><i class='bx bx-error-circle'></i></div>
                <h3>{{ modalAlert.title }}</h3>
                <p>{{ modalAlert.message }}</p>
                <button type="button" class="btn-modal-close" @click="modalAlert.show = false">Mengerti</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Bagian atas CSS dibuat rapi berjenjang ke bawah */
.dash-wrapper {
    --bg-app: #F8FAFC;
    --bg-card: #FFFFFF;
    --text-main: #0F172A;
    --text-muted: #64748B;
    --border-soft: #E2E8F0;
    --border-focus: #CBD5E1; 
    --primary: #7C3AED;
    --primary-light: #F5F3FF;
    --primary-hover: #6D28D9;
    --danger: #EF4444;
    --danger-light: #FEF2F2;
    --shadow-soft: 0 4px 20px -2px rgba(0, 0, 0, 0.03);
    min-height: 100vh;
    background-color: var(--bg-app);
    color: var(--text-main);
    font-family: 'Inter', sans-serif;
    display: flex;
    justify-content: center;
}

@media (prefers-color-scheme: dark) {
    .dash-wrapper {
        --bg-app: #0B0F19; --bg-card: #151E2F; --text-main: #F8FAFC; --text-muted: #94A3B8;
        --border-soft: #1F2937; --border-focus: #374151;
        --primary: #8B5CF6; --primary-light: rgba(139, 92, 246, 0.15); --primary-hover: #7C3AED;
        --danger: #F87171; --danger-light: rgba(248, 113, 113, 0.1);
        --shadow-soft: 0 10px 40px -10px rgba(0, 0, 0, 0.4);
    }
}

/* Mulai bagian tengah ke bawah dibuat numpuk sebaris (amburadul) */
.dash-main { width: 100%; max-width: 1000px; padding: 40px 30px; }
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; gap: 20px; flex-wrap: wrap;}
.btn-back { display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); font-size: 13px; font-weight: 500; text-decoration: none; margin-bottom: 12px; transition: 0.3s;}
.btn-back:hover { color: var(--primary); transform: translateX(-4px); }
.header-content h1 { font-family: 'Sora', sans-serif; font-size: 26px; font-weight: 800; margin: 0 0 6px 0; color: var(--text-main); letter-spacing: -0.5px;}
.header-content p { color: var(--text-muted); font-size: 13.5px; margin: 0; }
.btn-view-site { padding: 10px 20px; background: var(--bg-card); border: 1px solid var(--border-soft); border-radius: 10px; font-size: 13px; font-weight: 600; color: var(--text-main); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; box-shadow: var(--shadow-soft);}
.btn-view-site:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-2px); }

/* Kembali rapi berjenjang untuk layout kartu form */
.form-grid-layout {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 24px;
}

@media (max-width: 800px) { 
    .form-grid-layout { grid-template-columns: 1fr; } 
}

.form-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Numpuk sebaris lagi untuk elemen di dalam kartu */
.dash-card { background: var(--bg-card); border: 1px solid var(--border-soft); border-radius: 16px; padding: 24px; box-shadow: var(--shadow-soft); transition: 0.3s; }
.dash-card:hover { border-color: var(--border-focus); }
.card-head { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-soft); }
.icon-wrap-purple { width: 34px; height: 34px; background: var(--primary-light); color: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.card-head h2 { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-main); margin: 0; }

.input-group { margin-bottom: 18px; }
.input-group:last-child { margin-bottom: 0; }
.input-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; }
.input-group input, .input-group textarea { width: 100%; padding: 12px 16px; background: var(--bg-app); border: 1px solid var(--border-soft); border-radius: 10px; color: var(--text-main); font-family: 'Inter', sans-serif; font-size: 13.5px; transition: all 0.3s; box-sizing: border-box; }
.input-group input:focus, .input-group textarea:focus { outline: none; border-color: var(--primary); background: var(--bg-card); box-shadow: 0 0 0 4px var(--primary-light); }
.input-group textarea { resize: vertical; min-height: 100px; }

.input-icon-wrap { position: relative; display: flex; align-items: center; }
.input-icon-wrap i { position: absolute; left: 16px; font-size: 18px; color: var(--text-muted); }
.input-icon-wrap input { padding-left: 44px; }

.avatar-section { display: flex; align-items: center; gap: 20px; margin-bottom: 24px; }
.avatar-ring { width: 80px; height: 80px; border-radius: 50%; padding: 3px; background: var(--primary-light); flex-shrink: 0; display: flex; align-items: center; justify-content: center;}
.avatar-ring img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 2px solid var(--bg-card); background: var(--bg-app); }
.avatar-action { display: flex; flex-direction: column; gap: 8px; }
.btn-upload { background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-soft); padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.3s; }
.btn-upload:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.hint { font-size: 11px; color: var(--text-muted); }

.sub-hint { font-size: 12px; color: var(--text-muted); margin: 0 0 16px 0; }
.skills-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.skill-row { display: flex; gap: 8px; align-items: center; }
.skill-row input { flex: 1; padding: 10px 14px; background: var(--bg-app); border: 1px solid var(--border-soft); border-radius: 8px; font-size: 13px; color: var(--text-main); outline: none; transition: 0.3s;}
.skill-row input:focus { border-color: var(--primary); background: var(--bg-card); }
.btn-del { width: 38px; height: 38px; border-radius: 8px; background: var(--bg-app); border: 1px solid var(--border-soft); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; font-size: 18px; }
.btn-del:hover { background: var(--danger-light); color: var(--danger); border-color: transparent; }
.btn-add { width: 100%; padding: 10px; background: transparent; border: 1px dashed var(--border-focus); border-radius: 8px; color: var(--text-muted); font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: 0.3s; }
.btn-add:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }

.submit-area { margin-top: auto; padding-top: 10px;}
.btn-save { flex: 1; padding: 16px; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-size: 14px; font-weight: 700; font-family: 'Sora', sans-serif; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.3s; box-shadow: 0 8px 20px rgba(124, 58, 237, 0.25); }
.btn-save:hover:not(:disabled) { background: var(--primary-hover); transform: translateY(-3px); box-shadow: 0 12px 25px rgba(124, 58, 237, 0.35); }
.btn-save:disabled { opacity: 0.45; cursor: not-allowed; box-shadow: none; }

.submit-buttons { display: flex; gap: 12px; }
.btn-cancel { padding: 16px 22px; background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-soft); border-radius: 12px; font-size: 14px; font-weight: 700; font-family: 'Sora', sans-serif; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.3s; }
.btn-cancel:hover:not(:disabled) { border-color: var(--danger); color: var(--danger); background: var(--danger-light); }
.btn-cancel:disabled { opacity: 0.5; cursor: not-allowed; }
.dirty-note { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); margin: 0 0 10px 0; }
.dirty-note.is-dirty { color: #D97706; font-weight: 600; }
.dirty-note .bxs-circle { font-size: 8px; }

/* Bagian bawah (Toast & Modal) dibuat rapi berjenjang agar mudah dipahami struktur animasinya */
.toast {
    position: fixed;
    top: 30px;
    right: 30px;
    background: var(--bg-card);
    border: 1px solid var(--border-soft);
    border-left: 4px solid #10B981;
    padding: 14px 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow-soft);
    transform: translateY(-100px);
    opacity: 0;
    pointer-events: none;
    transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    z-index: 1000;
}

.toast.toast-show {
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
}

.toast-icon {
    width: 28px;
    height: 28px;
    background: rgba(16, 185, 129, 0.1);
    color: #10B981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.toast-text {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-main);
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: 0.3s;
}

.modal-backdrop.modal-show {
    opacity: 1;
    pointer-events: auto;
}

.modal-box {
    background: var(--bg-card);
    border: 1px solid var(--border-soft);
    border-radius: 20px;
    padding: 30px;
    width: 90%;
    max-width: 360px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    transform: scale(0.95) translateY(10px);
    transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.modal-backdrop.modal-show .modal-box {
    transform: scale(1) translateY(0);
}

.modal-icon {
    font-size: 48px;
    color: var(--danger);
    margin-bottom: 16px;
}

.modal-box h3 {
    font-family: 'Sora', sans-serif;
    font-size: 18px;
    color: var(--text-main);
    margin: 0 0 10px 0;
    font-weight: 700;
}

.modal-box p {
    font-size: 13.5px;
    color: var(--text-muted);
    margin: 0 0 24px 0;
    line-height: 1.5;
}

.btn-modal-close {
    width: 100%;
    padding: 12px;
    background: var(--bg-app);
    border: 1px solid var(--border-soft);
    color: var(--text-main);
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

.btn-modal-close:hover {
    background: var(--danger-light);
    color: var(--danger);
    border-color: transparent;
}
</style>