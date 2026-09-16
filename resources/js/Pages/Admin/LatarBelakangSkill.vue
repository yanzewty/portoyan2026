<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    profile: { type: Object, default: () => ({}) },
    dataKeahlian: { type: Array, default: () => [] }
});

const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success'); 

const displayToast = (msg, type = 'success') => {
    toastMessage.value = msg;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3500);
};

const modal = ref({
    isOpen: false, title: '', message: '', icon: 'bx-question-mark',
    color: '#4E9BE0', confirmText: 'Ya', showCancel: true, onConfirm: null
});

const openModal = (title, message, icon, color, confirmText, onConfirm, showCancel = true) => {
    modal.value = { isOpen: true, title, message, icon, color, confirmText, onConfirm, showCancel };
};

const closeModal = () => { modal.value.isOpen = false; };
const confirmAction = () => { if (modal.value.onConfirm) modal.value.onConfirm(); closeModal(); };

// Form Header kini disederhanakan: HANYA Judul dan Deskripsi
const formHeader = useForm({
    skill_title: props.profile.about_sub_2 || 'LATAR BELAKANG & SKILL',
    skill_desc: props.profile.about_2 || 'Dokumentasi kegiatan pemrograman web, desain UI/UX, dan organisasi sosial.'
});

const submitHeader = () => {
    if (!formHeader.isDirty) {
        openModal('Belum Ada Perubahan', 'Kamu belum mengubah kata apapun pada form header.', 'bx-info-circle', '#FF5F1F', 'Kembali', null, false);
    } else {
        openModal('Konfirmasi Simpan', 'Yakin ingin menyimpan perubahan pada teks header ini?', 'bx-save', '#4E9BE0', 'Ya, Simpan', () => {
            formHeader.post('/admin/latar-belakang-skill/header', {
                preserveScroll: true, onSuccess: () => displayToast('Teks Header Utama berhasil disimpan!', 'success')
            });
        }, true);
    }
};

const cancelFormHeader = () => {
    openModal('Batalkan Perubahan', 'Yakin ingin membatalkan? Ketikan barumu akan dikembalikan.', 'bx-x-circle', '#FF3B30', 'Ya, Batalkan', () => {
        formHeader.reset(); displayToast('Perubahan dibatalkan.', 'error');
    });
};

const formSkill = useForm({ modul: '', kategori: '', judul: '', deskripsi: '', gambar: null });
const imagePreview = ref('');
const fileInput = ref(null);

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (!file.type.match('image.*')) { displayToast('Format file harus gambar (JPG/PNG).', 'error'); resetFormAdd(); return; }
    if (file.size > 2 * 1024 * 1024) { displayToast('Ukuran maksimal 2MB.', 'error'); resetFormAdd(); return; }
    formSkill.gambar = file;
    imagePreview.value = URL.createObjectURL(file);
};

const resetFormAdd = () => {
    formSkill.reset(); imagePreview.value = '';
    if (fileInput.value) fileInput.value.value = '';
};

const cancelFormAdd = () => {
    openModal('Batalkan Pengisian', 'Yakin batal? Data modul baru ini akan dihapus.', 'bx-trash-alt', '#FF3B30', 'Ya, Batalkan', () => {
        resetFormAdd(); displayToast('Batal menambahkan modul.', 'error');
    });
};

const submitSkill = () => {
    if (!(formSkill.isDirty || formSkill.gambar !== null)) {
        openModal('Data Masih Kosong', 'Kamu belum mengisi data modul apapun.', 'bx-info-circle', '#8792A6', 'Kembali', null, false);
    } else {
        openModal('Simpan Modul Baru', 'Data modul sudah benar? Klik simpan untuk menambahkan.', 'bx-layer-plus', '#34C77B', 'Ya, Simpan', () => {
            formSkill.post('/admin/latar-belakang-skill', {
                preserveScroll: true,
                onSuccess: () => { resetFormAdd(); displayToast('Modul baru berhasil ditambahkan!', 'success'); },
                onError: () => displayToast('Gagal menyimpan! Periksa form.', 'error')
            });
        }, true);
    }
};

const deleteSkill = (id) => {
    openModal('Hapus Modul', 'Yakin ingin menghapus kartu ini permanen?', 'bx-trash', '#FF3B30', 'Ya, Hapus', () => {
        router.delete(`/admin/latar-belakang-skill/${id}`, { preserveScroll: true, onSuccess: () => displayToast('Modul berhasil dihapus!', 'success') });
    });
};

const getImageUrl = (item, index) => {
    if (item.gambar) return `/uploads/${item.gambar}`;
    if (index === 0) return '/uploads/1786586192_profil_IMG_20260707_112708_146.jpg';
    if (index === 1) return '/uploads/1787207896_keahlian_IMG_20260715_071115_152.jpg';
    return '/uploads/1786516895_g3_DSC07615.jpg';
};

const getDeskripsi = (item, index) => {
    if (item.deskripsi && item.deskripsi.trim() !== '') return item.deskripsi;
    if (index === 0) return "Membangun sistem website dinamis dan responsif menggunakan PHP dan framework Laravel...";
    if (index === 1) return "Merancang antarmuka web dan aplikasi (UI) yang ramah pengguna, estetis...";
    return "Aktif mengasah kepemimpinan dan komunikasi sosial...";
};

const truncate = (text, length) => text.length > length ? text.substring(0, length) + '...' : text;
</script>

<template>
    <Head title="Kelola Latar Belakang Skill" />

    <div class="admin-container">
        <div class="wrap-form">
            <!-- Header -->
            <div class="page-header">
                <div class="header-content">
                    <Link href="/admin" class="btn-back"><i class='bx bx-arrow-back'></i> Kembali ke Dashboard</Link>
                    <h1>Latar Belakang & Skill</h1>
                    <p>Kelola modul keahlian yang tertampil di halaman utama Latar Belakang & Skill.</p>
                </div>
                <a href="/#LatarBelakangSkill" target="_blank" class="btn-outline-glow">
                    <i class='bx bx-link-external'></i> Lihat Website
                </a>
            </div>

            <!-- FORM 1: HEADER UTAMA (Disederhanakan) -->
            <div class="premium-card">
                <div class="card-header-accent" style="--accent: var(--cyan);">
                    <i class='bx bx-text'></i> Teks Utama
                </div>
                <form @submit.prevent="submitHeader" class="card-body">
                    <div class="input-group">
                        <label>Judul Utama</label>
                        <input type="text" v-model="formHeader.skill_title" required placeholder="LATAR BELAKANG & SKILL">
                    </div>
                    <div class="input-group">
                        <label>Deskripsi</label>
                        <textarea v-model="formHeader.skill_desc" rows="3" required placeholder="Dokumentasi kegiatan..."></textarea>
                    </div>
                    <div class="action-footer">
                        <button v-if="formHeader.isDirty" type="button" class="btn-cancel" @click="cancelFormHeader">Batal</button>
                        <button type="submit" class="btn-save" :disabled="formHeader.processing">
                            <i :class="formHeader.processing ? 'bx bx-loader-alt bx-spin' : 'bx bx-save'"></i> 
                            {{ formHeader.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- LIST KARTU -->
            <div class="section-divider">
                <h3><i class='bx bx-grid-alt'></i> Modul Yang Tersimpan</h3>
            </div>
            
            <div class="skill-grid">
                <div v-for="(item, index) in dataKeahlian" :key="item.id" class="skill-card">
                    <div class="card-overlay-actions">
                        <Link :href="`/admin/latar-belakang-skill/${item.id}/edit`" class="act-btn edit"><i class='bx bx-edit'></i></Link>
                        <button @click="deleteSkill(item.id)" class="act-btn delete"><i class='bx bx-trash'></i></button>
                    </div>
                    <div class="skill-img"><img :src="getImageUrl(item, index)" alt="Cover"></div>
                    <div class="skill-info">
                        <span class="skill-modul">{{ item.modul }}</span>
                        <h4 class="skill-title">{{ item.judul }}</h4>
                        <p class="skill-desc">{{ truncate(getDeskripsi(item, index), 80) }}</p>
                        <div class="skill-meta">
                            <span class="skill-cat">{{ item.kategori }}</span>
                            <Link :href="`/admin/latar-belakang-skill/${item.id}/edit`" class="link-edit">Edit <i class='bx bx-right-arrow-alt'></i></Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM 2: TAMBAH MODUL BARU -->
            <div class="premium-card" style="margin-top: 50px;">
                <div class="card-header-accent" style="--accent: #10B981;">
                    <i class='bx bx-plus-circle'></i> Tambah Modul Baru
                </div>
                <form @submit.prevent="submitSkill" class="card-body">
                    <div class="layout-side-by-side">
                        
                        <!-- Upload Kiri -->
                        <div class="upload-zone" :class="{'has-image': imagePreview}" @click="$refs.fileInput.click()">
                            <img v-if="imagePreview" :src="imagePreview" alt="Preview">
                            <div v-else class="upload-prompt">
                                <div class="icon-circle"><i class='bx bx-cloud-upload'></i></div>
                                <h4>Unggah Gambar</h4>
                                <span>Format JPG/PNG (Maks 2MB)</span>
                            </div>
                        </div>
                        <input type="file" ref="fileInput" accept="image/*" style="display: none;" @change="handleFileUpload">

                        <!-- Form Kanan -->
                        <div class="form-zone">
                            <div class="grid-2">
                                <div class="input-group">
                                    <label>Teks Modul</label>
                                    <input type="text" v-model="formSkill.modul" required placeholder="MODUL / 01">
                                </div>
                                <div class="input-group">
                                    <label>Kategori</label>
                                    <input type="text" v-model="formSkill.kategori" required placeholder="DEVELOPMENT">
                                </div>
                            </div>
                            <div class="input-group">
                                <label>Judul Keahlian</label>
                                <input type="text" v-model="formSkill.judul" required placeholder="Pemrograman Web">
                            </div>
                            <div class="input-group">
                                <label>Deskripsi Lengkap</label>
                                <textarea v-model="formSkill.deskripsi" rows="5" required placeholder="Detail keahlian..."></textarea>
                            </div>
                            <div class="action-footer" style="margin-top: 30px;">
                                <button v-if="formSkill.isDirty || formSkill.gambar" type="button" class="btn-cancel" @click="cancelFormAdd">Batal</button>
                                <button type="submit" class="btn-save btn-green" :disabled="formSkill.processing">
                                    <i :class="formSkill.processing ? 'bx bx-loader-alt bx-spin' : 'bx bx-check'"></i> 
                                    {{ formSkill.processing ? 'Menyimpan...' : 'Simpan Modul Baru' }}
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Teleports -->
    <Teleport to="body">
        <div :class="['toast-notification', toastType, { 'show': showToast }]">
            <i :class="toastType === 'success' ? 'bx bx-check-circle' : 'bx bx-error-circle'" class="toast-icon"></i>
            <span>{{ toastMessage }}</span>
        </div>
        <div v-if="modal.isOpen" class="modal-overlay" @click="closeModal"></div>
        <div v-if="modal.isOpen" class="custom-modal">
            <div class="modal-icon" :style="{ color: modal.color, background: modal.color + '1A' }"><i :class="'bx ' + modal.icon"></i></div>
            <h3 class="modal-title">{{ modal.title }}</h3>
            <p class="modal-desc">{{ modal.message }}</p>
            <div class="modal-actions">
                <button v-if="modal.showCancel" type="button" class="btn-modal-cancel" @click="closeModal">Batal</button>
                <button type="button" class="btn-modal-confirm" :style="{ background: modal.color }" @click="modal.onConfirm ? confirmAction() : closeModal()">{{ modal.confirmText }}</button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.admin-container {
    --bg: #0A0E17; --panel: #10151F; --panel-2: #141B29; --line: #232D3E; 
    --text: #EAEEF5; --dim: #8792A6; --cyan: #4E9BE0; --primary: #3763E0;
    background-color: var(--bg); color: var(--text); font-family: 'Inter', sans-serif;
    min-height: 100vh; padding: 40px; box-sizing: border-box;
}
.wrap-form { max-width: 1100px; margin: 0 auto; padding-bottom: 60px; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; border-bottom: 1px solid var(--line); padding-bottom: 20px;}
.btn-back { display: inline-flex; align-items: center; gap: 6px; color: var(--dim); font-size: 13.5px; text-decoration: none; margin-bottom: 12px; transition: 0.3s;}
.btn-back:hover { color: var(--cyan); transform: translateX(-4px);}
.header-content h1 { font-family: 'Sora', sans-serif; font-size: 32px; font-weight: 800; color: #fff; margin: 0 0 5px 0; }
.header-content p { color: var(--dim); font-size: 14px; margin: 0; }
.btn-outline-glow { padding: 10px 20px; border: 1px solid var(--line); border-radius: 10px; font-size: 13px; color: var(--text); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; background: var(--panel); box-shadow: 0 4px 15px rgba(0,0,0,0.2);}
.btn-outline-glow:hover { border-color: var(--cyan); color: var(--cyan); background: rgba(78, 155, 224, 0.05); box-shadow: 0 0 15px rgba(78, 155, 224, 0.2);}

/* CARD */
.premium-card { background: var(--panel); border: 1px solid var(--line); border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.3); overflow: hidden; }
.card-header-accent { background: linear-gradient(90deg, rgba(16,21,31,1) 0%, rgba(20,27,41,1) 100%); padding: 24px 30px; border-bottom: 1px solid var(--line); border-top: 3px solid var(--accent); font-family: 'Sora', sans-serif; font-weight: 700; font-size: 16px; color: #fff; display: flex; align-items: center; gap: 10px; }
.card-header-accent i { color: var(--accent); font-size: 22px; }
.card-body { padding: 30px; }

/* FORM */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.input-group { margin-bottom: 24px; }
.input-group label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: var(--dim); margin-bottom: 10px; }
.input-group input, .input-group textarea { width: 100%; background: var(--bg); border: 1px solid var(--line); color: #fff; padding: 14px 18px; border-radius: 12px; font-size: 14px; transition: 0.3s; box-sizing: border-box; font-family: 'Inter', sans-serif;}
.input-group input:focus, .input-group textarea:focus { outline: none; border-color: var(--cyan); background: var(--panel-2); box-shadow: 0 0 0 4px rgba(78, 155, 224, 0.1); }
.input-group textarea { resize: vertical; }

.action-footer { display: flex; justify-content: flex-end; gap: 12px; border-top: 1px dashed var(--line); padding-top: 24px; }
.btn-cancel { padding: 12px 24px; background: transparent; border: 1px solid var(--line); color: var(--text); border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s; }
.btn-cancel:hover { background: rgba(255,255,255,0.05); }
.btn-save { padding: 12px 28px; background: var(--primary); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(55, 99, 224, 0.2); }
.btn-save:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(55, 99, 224, 0.4); background: #4676FA;}
.btn-green { background: #10B981; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2); }
.btn-green:hover:not(:disabled) { background: #059669; box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); }


.section-divider { margin: 50px 0 20px 0; display: flex; align-items: center; justify-content: space-between;}
.section-divider h3 { font-family: 'Sora', sans-serif; font-size: 20px; font-weight: 700; color: #fff; margin: 0; display: flex; align-items: center; gap: 10px; }
.section-divider h3 i { color: var(--cyan); }

.skill-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }
.skill-card { background: var(--panel); border: 1px solid var(--line); border-radius: 18px; overflow: hidden; position: relative; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.2);}
.skill-card:hover { transform: translateY(-10px); border-color: var(--cyan); box-shadow: 0 20px 40px rgba(78, 155, 224, 0.15); }
.skill-img { height: 180px; width: 100%; overflow: hidden; position: relative; }
.skill-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
.skill-card:hover .skill-img img { transform: scale(1.1); }
.skill-img::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, var(--panel), transparent); }
.skill-info { padding: 24px; position: relative; z-index: 2; margin-top: -40px; display: flex; flex-direction: column; flex-grow: 1;}
.skill-modul { display: inline-block; font-family: 'JetBrains Mono', monospace; font-size: 11px; background: rgba(78, 155, 224, 0.1); color: var(--cyan); padding: 4px 10px; border-radius: 6px; font-weight: 700; margin-bottom: 12px; align-self: flex-start;}
.skill-title { font-family: 'Sora', sans-serif; font-size: 18px; color: #fff; margin: 0 0 12px 0; font-weight: 700;}
.skill-desc { font-size: 13px; color: var(--dim); line-height: 1.6; margin: 0 0 20px 0; flex-grow: 1;}
.skill-meta { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--line); padding-top: 16px;}
.skill-cat { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #C9A24A; font-weight: 700;}
.link-edit { color: var(--dim); font-size: 13px; text-decoration: none; display: flex; align-items: center; gap: 4px; transition: 0.3s; font-weight: 600;}
.skill-card:hover .link-edit { color: var(--cyan); }

.card-overlay-actions { position: absolute; top: 16px; right: 16px; display: flex; gap: 8px; z-index: 10; opacity: 0; transform: translateY(-10px); transition: 0.3s; }
.skill-card:hover .card-overlay-actions { opacity: 1; transform: translateY(0); }
.act-btn { width: 36px; height: 36px; border-radius: 10px; border: none; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; cursor: pointer; text-decoration: none; backdrop-filter: blur(4px); transition: 0.2s;}
.act-btn.edit { background: rgba(78, 155, 224, 0.8); border: 1px solid var(--cyan); }
.act-btn.edit:hover { background: var(--cyan); }
.act-btn.delete { background: rgba(239, 68, 68, 0.8); border: 1px solid #EF4444; }
.act-btn.delete:hover { background: #EF4444; }

/* ADD NEW */
.layout-side-by-side { display: grid; grid-template-columns: 340px 1fr; gap: 40px; align-items: stretch; }
@media (max-width: 900px) { .layout-side-by-side { grid-template-columns: 1fr; } }
.upload-zone { width: 100%; height: 100%; min-height: 350px; background: var(--bg); border: 2px dashed var(--line); border-radius: 16px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; cursor: pointer; transition: 0.3s;}
.upload-zone:hover { border-color: var(--cyan); background: rgba(78, 155, 224, 0.05); }
.upload-zone.has-image { border-style: solid; border-color: var(--cyan); }
.upload-zone img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;}
.upload-prompt { text-align: center; color: var(--dim); transition: 0.3s; position: relative; z-index: 2;}
.upload-zone:hover .upload-prompt { color: var(--cyan); transform: scale(1.05); }
.icon-circle { width: 64px; height: 64px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px auto;}
.upload-prompt h4 { font-family: 'Sora', sans-serif; margin: 0 0 8px 0; color: #fff; font-size: 16px;}
.upload-prompt span { font-size: 12px; }
</style>

<style>
/* Toast & Modal Global CSS */
.toast-notification { position: fixed; top: 30px; right: 30px; transform: translateY(-100px); opacity: 0; padding: 16px 24px; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); background: rgba(16, 21, 31, 0.95); backdrop-filter: blur(10px); display: flex; align-items: center; gap: 12px; font-family: 'Inter', sans-serif; font-size: 13.5px; font-weight: 600; z-index: 9999; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.toast-notification.show { transform: translateY(0); opacity: 1; }
.toast-notification.error { border: 1px solid #FF3B30; color: #FF3B30; }
.toast-notification.success { border: 1px solid #10B981; color: #10B981; }
.toast-icon { font-size: 24px; }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(10, 14, 23, 0.85); backdrop-filter: blur(6px); z-index: 9998; }
.custom-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #10151F; border: 1px solid #232D3E; border-radius: 20px; padding: 35px 30px; width: 90%; max-width: 400px; text-align: center; z-index: 9999; box-shadow: 0 25px 50px rgba(0,0,0,0.5); animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); font-family: 'Inter', sans-serif; }
@keyframes modalPop { 0% { opacity: 0; transform: translate(-50%, -40%) scale(0.9); } 100% { opacity: 1; transform: translate(-50%, -50%) scale(1); } }
.modal-icon { width: 65px; height: 65px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px; }
.modal-title { color: #fff; font-family: 'Sora', sans-serif; font-size: 20px; font-weight: 700; margin-bottom: 12px; }
.modal-desc { color: #8792A6; font-size: 14px; line-height: 1.6; margin-bottom: 30px; }
.modal-actions { display: flex; gap: 12px; justify-content: center; }
.btn-modal-cancel { flex: 1; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; background: transparent; color: #EAEEF5; border: 1px solid #232D3E; transition: 0.2s;}
.btn-modal-cancel:hover { background: rgba(255,255,255,0.05); }
.btn-modal-confirm { flex: 1; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; color: #fff; border: none; transition: 0.2s; box-shadow: 0 4px 15px rgba(0,0,0,0.2);}
.btn-modal-confirm:hover { filter: brightness(1.1); transform: translateY(-2px); }
</style>