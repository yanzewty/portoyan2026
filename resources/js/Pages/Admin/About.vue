<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';

// Tarik data profil sama dari database
const props = defineProps({
    profile: { type: Object, default: () => ({}) },
    about: { type: Object, default: () => ({}) },
    panels: { type: Array, default: () => [] }
});

const page = usePage();
const showSuccessToast = ref(false);
const toastMessage = ref('');

// Munculin notif sukses di pojok, ilang sendiri pas 3 detik
const showToast = (message) => {
    toastMessage.value = message;
    showSuccessToast.value = true;
    setTimeout(() => { showSuccessToast.value = false; }, 3000);
};

// Buat nampilin atau nyembunyiin pop-up konfirmasi hapus
const showDeleteModal = ref(false);
const panelToDelete = ref(null);
const isDeleting = ref(false); // Indikator loading tambahan buat tombol hapus

// Form buat nampung teks utama tentang saya
const formAbout = useForm({
    about_sub_1: props.about?.tag || '',
    about_title: props.about?.title || '',
    about_1: props.about?.description || ''
});

// Lempar editan teks utama ke backend biar disimpen
const submitAbout = () => {
    formAbout.post('/admin/about', {
        preserveScroll: true,
        onSuccess: () => showToast('Teks utama Tentang Saya berhasil diperbarui!')
    });
};

// Form buat nambahin kotak cerita baru
const formPanel = useForm({
    tag: '',
    title: '',
    desc_1: ''
});

// Lempar data cerita baru ke backend trus kosongin formnya lagi
const submitPanel = () => {
    formPanel.post('/admin/panels', {
        preserveScroll: true,
        onSuccess: () => {
            formPanel.reset();
            showToast('Data berhasil ditambahkan!'); // Teks diubah
        }
    });
};

// Nangkep ID cerita yang mau dihapus trus munculin pop-up
const confirmDelete = (id) => {
    panelToDelete.value = id;
    showDeleteModal.value = true;
};

// Kalau gajadi hapus, tutup lagi pop-up nya
const cancelDelete = () => {
    if(isDeleting.value) return; // Cegah nutup kalo lagi proses hapus
    showDeleteModal.value = false;
    panelToDelete.value = null;
};

// Eksekusi hapus data ke database dengan Error Handling
const executeDelete = () => {
    if (panelToDelete.value) {
        isDeleting.value = true;
        
        router.delete(`/admin/panels/${panelToDelete.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Data berhasil dihapus!'); // Teks diubah
                showDeleteModal.value = false;
                panelToDelete.value = null;
            },
            onError: (errors) => {
                console.error("Gagal menghapus:", errors);
                showToast('Terjadi kesalahan saat menghapus data.'); // Teks diubah
                showDeleteModal.value = false; 
                panelToDelete.value = null;
            },
            onFinish: () => {
                isDeleting.value = false;
            }
        });
    } else {
        console.error("Gagal: ID tidak valid (undefined)");
        showDeleteModal.value = false;
    }
};
</script>

<template>
    <Head title="Kelola Tentang Saya" />

    <div class="admin-container">
        <div class="wrap-form">
            
            <!-- Bagian atas halaman -->
            <div class="header-flex">
                <div>
                    <Link href="/admin" class="btn-back"><i class='bx bx-arrow-back'></i> Kembali ke Dashboard</Link>
                    <h1 class="page-title">Tentang Saya (About)</h1>
                    <p class="page-desc">Kelola paragraf utama profil dan tambahkan cerita tambahan.</p> <!-- Teks diubah -->
                </div>
                <a href="/#About" target="_blank" class="btn-outline">
                    <i class='bx bx-link-external'></i> Lihat Website
                </a>
            </div>

            


            <!-- Kotak isi form paragraf utama -->
            <div class="card-form" style="--accent: var(--primary);">
                <div class="form-title"><i class='bx bx-user-pin'></i> Teks Utama Tentang Saya</div>
                
                <form @submit.prevent="submitAbout">
                    <div class="grid2">
                        <div class="form-group">
                            <label>Tag / Sub-judul</label>
                            <input type="text" v-model="formAbout.about_sub_1" placeholder="Contoh: 01 / TENTANG SAYA" required>
                        </div>
                        <div class="form-group">
                            <label>Judul Utama</label>
                            <input type="text" v-model="formAbout.about_title" placeholder="Membangun Solusi Digital..." required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Paragraf Deskripsi</label>
                        <textarea v-model="formAbout.about_1" rows="5" placeholder="Tuliskan cerita singkat tentang dirimu..." required></textarea>
                    </div>
                    
                    <div class="submit-wrap">
                        <button type="submit" class="submit-btn" :disabled="formAbout.processing">
                            <i :class="formAbout.processing ? 'bx bx-loader-alt bx-spin' : 'bx bx-save'"></i> 
                            {{ formAbout.processing ? 'Menyimpan...' : 'Simpan Teks Utama' }}
                        </button>
                    </div>
                </form>
            </div>


            <div class="panel-grid">
                
                <!-- Kotak form khusus bikin cerita baru -->
                <div class="card-form" style="--accent: var(--cyan); margin-bottom: 0;">

                    <!-- TEKS DIUBAH DI AREA INI -->
                    <div class="form-title" style="color: var(--cyan);"><i class='bx bx-plus-circle'></i> Tambah Cerita Baru</div>
                    <p class="sub-hint">Pecah ceritamu ke dalam beberapa bagian (contoh: Visi Misi, Fokus).</p>
                    
                    <form @submit.prevent="submitPanel">
                        <div class="form-group">
                            <label>Tag (Sub-judul)</label>
                            <input type="text" v-model="formPanel.tag" placeholder="Misal: 02 / VISI" required>
                        </div>
                        <div class="form-group">
                            <label>Judul</label>
                            <input type="text" v-model="formPanel.title" placeholder="Fokus & Tujuan" required>
                        </div>
                        <div class="form-group">
                            <label>Isi Deskripsi</label>
                            <textarea v-model="formPanel.desc_1" rows="4" placeholder="Tuliskan isinya di sini..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn-add-solid" :disabled="formPanel.processing">
                            <i :class="formPanel.processing ? 'bx bx-loader-alt bx-spin' : 'bx bx-plus'"></i> 
                            {{ formPanel.processing ? 'Menambahkan...' : 'Tambahkan ke Daftar' }}
                        </button>
                    </form>
                </div>

                <!-- Menampilkan semua cerita yang udah dibikin -->
                <div class="card-form" style="--accent: var(--gold); margin-bottom: 0;">
                    <!-- TEKS DIUBAH DI AREA INI -->
                    <div class="form-title"><i class='bx bx-list-ul'></i> Daftar Cerita Tambahan <span class="badge-count">{{ panels.length }}</span></div>
                    
                    <div v-if="panels.length === 0" class="empty-state">
                        <i class='bx bx-folder-open'></i>
                        <p>Belum ada cerita tambahan yang dibuat.</p>
                    </div>

                    <div v-else class="panel-list">
                        <div v-for="panel in panels" :key="panel.id" class="panel-item">
                            <div class="panel-info">
                                <span class="panel-tag">{{ panel.tag }}</span>
                                <h4>{{ panel.title }}</h4>
                                <p style="white-space: pre-wrap; line-height: 1.6; padding-right: 10px;">{{ panel.description }}</p>
                            </div>
                            <div class="panel-actions">
                                <Link :href="`/admin/panels/${panel.id}/edit`" class="btn-edit"><i class='bx bx-edit'></i> Edit</Link>
                                <button type="button" @click="confirmDelete(panel.id)" class="btn-delete"><i class='bx bx-trash'></i> Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Tampilan pop-up hitam kalau klik tombol hapus -->
        <div class="modal-overlay" :class="{'show': showDeleteModal}" @click.self="cancelDelete">
            <div class="modal-card">
                <div class="modal-icon"><i class='bx bx-trash'></i></div>
                <!-- TEKS DIUBAH DI AREA INI -->
                <h3>Hapus Cerita?</h3>
                <p>Data cerita ini akan dihapus secara permanen dan tidak dapat dikembalikan.</p>
                <div class="modal-actions">
                    <button type="button" @click="cancelDelete" class="btn-modal-cancel" :disabled="isDeleting">Batal</button>
                    <button type="button" @click="executeDelete" class="btn-modal-delete" :disabled="isDeleting">
                        <span v-if="isDeleting"><i class='bx bx-loader-alt bx-spin'></i> Menghapus...</span>
                        <span v-else>Ya, Hapus</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Kotak ijo notif sukses di pojok atas -->
        <div class="toast" :class="{'toast-show': showSuccessToast || $page.props.flash?.success_msg}">
            <div class="toast-icon"><i class='bx bx-check-circle'></i></div>
            <div class="toast-text">{{ toastMessage || $page.props.flash?.success_msg }}</div>
        </div>

    </div>
</template>

<style scoped>
/* Pengaturan warna tema dasar web (Sama seperti sebelumnya) */
.admin-container {
  --bg: #0A0E17; --panel: #10151F;
   --panel-2: #141B29; --line: #232D3E; 
    --text: #EAEEF5; --dim: #8792A6;
  --primary: #3763E0; --cyan: #4E9BE0;
   --gold: #C9A24A; --danger: #FF5F56;
  background-color: var(--bg);
   color: var(--text);
    font-family: 'Inter', sans-serif;
  min-height: 100vh;
   padding: 40px; box-sizing: border-box;
    position: relative;
}

.wrap-form {
  max-width: 1000px;
   margin: 0 auto;
    padding-bottom: 60px;
}

.header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;}
.btn-back { display: inline-flex; align-items: center; gap: 6px; color: var(--dim); font-size: 13px; text-decoration: none; margin-bottom: 10px; transition: 0.2s;}
.btn-back:hover { color: var(--cyan); transform: translateX(-4px);}
.page-title { font-family: 'Sora', sans-serif; font-size: 28px; margin: 0 0 5px 0; font-weight: 700; color: #fff;}
.page-desc { color: var(--dim); font-size: 14px; margin: 0; }
.btn-outline { padding: 10px 18px; border: 1px solid var(--line); border-radius: 10px; font-size: 13px; color: var(--text); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; background: var(--panel);}
.btn-outline:hover { border-color: var(--cyan); color: var(--cyan); background: rgba(78,155,224,0.1); }

.card-form {
  background: var(--panel);
   border: 1px solid var(--line);
    border-top: 3px solid var(--accent, var(--line));
  border-radius: 16px;
   padding: 30px;
  margin-bottom: 24px;
   box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.form-title {
  font-size: 15px;
   font-weight: 700;
    color: var(--text);
  margin-bottom: 24px;
   display: flex; align-items: center;
    gap: 8px; text-transform: uppercase; letter-spacing: 0.5px;
}

.form-title i { color: var(--accent); font-size: 20px; }
.badge-count { background: rgba(201, 162, 74, 0.15); color: var(--gold); padding: 2px 8px; border-radius: 20px; font-size: 12px; margin-left: 6px;}
.sub-hint { font-size: 13px; color: var(--dim); margin: -10px 0 20px 0; }
.form-group { margin-bottom: 20px; }
label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--dim); display: block; margin-bottom: 8px; }
input[type=text], textarea { width: 100%; padding: 14px 16px; background: var(--bg); border: 1px solid var(--line); border-radius: 10px; color: var(--text); font-size: 13.5px; transition: border-color 0.2s; box-sizing: border-box;}
input:focus, textarea:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 0 3px rgba(78,155,224,0.1);}
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

.panel-grid {
  display: grid;
   grid-template-columns: 1fr 1.3fr;
    gap: 24px;
}

@media (max-width: 800px) {
  .panel-grid {
    grid-template-columns: 1fr;
  }
   .grid2 {
    grid-template-columns: 1fr;
   }
}

.submit-wrap { display: flex; justify-content: flex-end; margin-top: 20px; }
.submit-btn { width: auto; padding: 14px 32px; border: none; border-radius: 12px; cursor: pointer; background: var(--primary); color: #fff; font-size: 14px; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; letter-spacing: 0.5px;}
.submit-btn:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(55, 99, 224, 0.4); }
.btn-add-solid { width: 100%; padding: 14px; background: rgba(78,155,224,0.1); color: var(--cyan); border: 1px solid rgba(78,155,224,0.3); border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s; }
.btn-add-solid:hover:not(:disabled) { background: var(--cyan); color: #000; }

.empty-state {
  text-align: center;
   padding: 40px 20px;
    background: var(--bg);
  border: 1px dashed var(--line);
   border-radius: 12px;
    color: var(--dim); font-size: 13px;
}

.empty-state i {
  font-size: 32px;
   margin-bottom: 10px;
    color: var(--line);
}

.panel-actions {
  display: flex;
   gap: 10px; flex-shrink: 0;
    align-items: center;
}

.panel-list { display: flex; flex-direction: column; gap: 14px; }
.panel-item { background: var(--panel-2); border: 1px solid var(--line); border-radius: 14px; padding: 16px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; transition: 0.3s; }
.panel-item:hover { border-color: var(--gold); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.3);}
.panel-info { flex: 1; }
.panel-tag { font-family: 'JetBrains Mono', monospace; font-size: 10px; color: #000; background: var(--gold); padding: 3px 8px; border-radius: 6px; font-weight: 700; margin-bottom: 8px; display: inline-block; }
.panel-info h4 { font-family: 'Sora', sans-serif; font-size: 15px; margin: 0 0 6px 0; color: #fff; }
.panel-info p { font-size: 12px; color: var(--dim); line-height: 1.5; margin: 0; }
.btn-edit { background: rgba(78,155,224,0.1); border: 1px solid rgba(78,155,224,0.3); color: var(--cyan); padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
.btn-edit:hover { background: var(--cyan); color: #000; }
.btn-delete { background: rgba(255,95,86,0.1); border: 1px solid rgba(255,95,86,0.3); color: var(--danger); padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
.btn-delete:hover { background: var(--danger); color: #fff; }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(10, 14, 23, 0.85); backdrop-filter: blur(5px); z-index: 2000; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: 0.3s; }
.modal-overlay.show { opacity: 1; visibility: visible; }
.modal-card { background: var(--panel); border: 1px solid var(--line); border-top: 3px solid var(--danger); padding: 30px; border-radius: 16px; width: 90%; max-width: 360px; text-align: center; box-shadow: 0 15px 40px rgba(0,0,0,0.4); transform: translateY(20px); transition: 0.3s;}
.modal-overlay.show .modal-card { transform: translateY(0); }
.modal-icon { width: 60px; height: 60px; background: rgba(255, 95, 86, 0.1); color: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px auto; }
.modal-card h3 { font-family: 'Sora', sans-serif; font-size: 18px; color: #fff; margin: 0 0 10px 0; }
.modal-card p { font-size: 13px; color: var(--dim); line-height: 1.5; margin: 0 0 24px 0; }
.modal-actions { display: flex; gap: 12px; }
.btn-modal-cancel { flex: 1; padding: 12px; background: transparent; border: 1px solid var(--line); color: var(--text); border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s; }
.btn-modal-cancel:hover:not(:disabled) { background: rgba(255,255,255,0.05); }
.btn-modal-cancel:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-modal-delete { flex: 1; padding: 12px; background: var(--danger); border: none; color: #fff; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 15px rgba(255, 95, 86, 0.2); display: flex; align-items: center; justify-content: center; gap: 8px;}
.btn-modal-delete:hover:not(:disabled) { background: #e04a42; transform: translateY(-2px); }
.btn-modal-delete:disabled { opacity: 0.7; cursor: not-allowed; transform: none; box-shadow: none; }
.toast { position: fixed; top: 30px; right: 30px; background: rgba(16, 21, 31, 0.95); border: 1px solid var(--cyan); color: var(--cyan); padding: 14px 20px; border-radius: 14px; display: flex; align-items: center; gap: 12px; font-size: 13.5px; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,0.5), 0 0 15px rgba(78,155,224,0.2); transform: translateY(-100px); opacity: 0; visibility: hidden; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 999; backdrop-filter: blur(8px); }
.toast.toast-show { transform: translateY(0); opacity: 1; visibility: visible; }
.toast-icon { font-size: 22px; }
</style>