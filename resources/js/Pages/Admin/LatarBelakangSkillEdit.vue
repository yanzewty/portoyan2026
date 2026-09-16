<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({ item: { type: Object, required: true } });

const isEditing = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success');

const displayToast = (msg, type = 'success') => {
    toastMessage.value = msg; toastType.value = type;
    showToast.value = true; setTimeout(() => { showToast.value = false; }, 3500);
};

const getFallbackDeskripsi = (item) => {
    if (item?.deskripsi && item.deskripsi.trim() !== '') return item.deskripsi;
    if (item?.id == 1 || (item?.modul && item.modul.includes('01'))) return "Membangun sistem website dinamis dan responsif menggunakan PHP dan framework Laravel. Berpengalaman dalam merancang arsitektur database, integrasi API, dan memastikan keamanan serta performa website tetap optimal di berbagai perangkat.";
    if (item?.id == 2 || (item?.modul && item.modul.includes('02'))) return "Merancang antarmuka web dan aplikasi (UI) yang ramah pengguna, estetis, dan memiliki alur interaksi yang jelas. Mampu membuat aset desain grafis dan poster digital untuk kebutuhan promosi kampanye atau media sosial yang menarik.";
    return "Aktif mengasah kepemimpinan dan komunikasi sosial. Berpengalaman mengurus tata kelola administrasi, merancang program kerja, dan berkolaborasi dalam tim untuk mencapai tujuan bersama organisasi.";
};

const getFallbackGambar = (item) => {
    if (item?.gambar) return `/uploads/${item.gambar}`;
    if (item?.id == 1 || (item?.modul && item.modul.includes('01'))) return '/uploads/1786586192_profil_IMG_20260707_112708_146.jpg';
    if (item?.id == 2 || (item?.modul && item.modul.includes('02'))) return '/uploads/1787207896_keahlian_IMG_20260715_071115_152.jpg';
    return '/uploads/1786516895_g3_DSC07615.jpg';
};

const form = useForm({
    modul: props.item?.modul || '', kategori: props.item?.kategori || '',
    judul: props.item?.judul || '', deskripsi: getFallbackDeskripsi(props.item), gambar: null
});

const imagePreview = ref('');
const fileInput = ref(null);

const triggerUpload = () => {
    if (isEditing.value) fileInput.value.click();
    else displayToast('Klik "Mulai Edit" di bawah terlebih dahulu!', 'error');
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (!file.type.match('image.*')) { displayToast('File harus gambar (JPG, PNG).', 'error'); resetImageOnly(); return; }
    if (file.size > 2 * 1024 * 1024) { displayToast('Ukuran maksimal: 2MB.', 'error'); resetImageOnly(); return; }
    form.gambar = file; imagePreview.value = URL.createObjectURL(file);
};

const resetImageOnly = () => { form.gambar = null; imagePreview.value = ''; if (fileInput.value) fileInput.value.value = ''; };

const cancelEdit = () => { isEditing.value = false; form.reset(); resetImageOnly(); displayToast('Mode edit dibatalkan.', 'success'); };

const submit = () => {
    form.post(`/admin/latar-belakang-skill/${props.item.id}/update`, {
        preserveScroll: true,
        onSuccess: () => {
            displayToast('Perubahan disimpan! Mengalihkan...', 'success');
            setTimeout(() => { router.get('/admin/latar-belakang-skill'); }, 1500); 
        },
        onError: () => displayToast('Gagal menyimpan! Periksa isian.', 'error')
    });
};
</script>

<template>
    <Head title="Detail Modul Keahlian" />

    <div class="admin-container">
        <div class="wrap-form">
            <!-- Header -->
            <div class="page-header">
                <div class="header-content">
                    <Link href="/admin/latar-belakang-skill" class="btn-back"><i class='bx bx-arrow-back'></i> Kembali ke List Modul</Link>
                    <h1>{{ isEditing ? 'Edit Data Modul' : 'Detail Data Modul' }}</h1>
                    <p>{{ isEditing ? 'Silakan perbarui teks atau ganti gambar.' : 'Form dalam mode baca. Klik Mulai Edit untuk mengubah data.' }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="premium-card">
                <div class="card-header-accent" :style="isEditing ? '--accent: var(--primary);' : '--accent: var(--dim);'">
                    <i :class="isEditing ? 'bx bx-edit' : 'bx bx-lock-alt'"></i> 
            {{ isEditing ? 'Membuka Akses Edit Data' : 'Data Saat Ini Terkunci' }}
                </div>
                
                <div class="card-body">
                    <div class="layout-side-by-side">
                        <!-- Upload Kiri -->
                        <div class="upload-col">
                            <label class="section-label">GAMBAR ILUSTRASI</label>
                            <div class="upload-zone" :class="{ 'disabled-zone': !isEditing, 'has-image': imagePreview }" @click="triggerUpload">
             <img v-if="imagePreview" :src="imagePreview" alt="Baru" class="preview-img new">
               <img v-else :src="getFallbackGambar(props.item)" alt="Lama" class="preview-img old" :class="{'dimmed': isEditing}">
                                
                     <div v-if="!imagePreview" class="upload-prompt">
   <div class="icon-circle"><i :class="isEditing ? 'bx bx-cloud-upload' : 'bx bx-lock'"></i></div>
<h4>{{ isEditing ? 'Ganti Foto' : 'Terkunci' }}</h4>
                 </div>
               </div>
                            <input type="file" ref="fileInput" accept="image/*" style="display: none;" @change="handleFileUpload">
                        </div>

                        <!-- Form Kanan -->
                        <div class="form-zone">
                            <label class="section-label">INFORMASI TEKS</label>
                            <div class="grid-2">
            <div class="input-group">
                      <label>Teks Modul (Atas)</label>
           <input type="text" v-model="form.modul" required :disabled="!isEditing">
                                </div>
                                <div class="input-group">
            <label>Kategori (Bawah)</label>
                                    <input type="text" v-model="form.kategori" required :disabled="!isEditing">
              </div>
                            </div>


               <div class="input-group">
                <label>Judul Keahlian Utama</label>
                      <input type="text" v-model="form.judul" required class="input-bold" :disabled="!isEditing">
                            </div>
                     <div class="input-group">
                            <label>Deskripsi Panjang</label>
                            <textarea v-model="form.deskripsi" rows="6" required :disabled="!isEditing"></textarea>
                            </div>
                            
                            <div class="action-footer" style="margin-top: 30px;">
                         <button v-if="!isEditing" type="button" @click="isEditing = true" class="btn-save" style="width: 100%; justify-content: center;">
                                    <i class='bx bx-edit-alt'></i> Buka Kunci & Mulai Edit
                                </button>
                                <template v-else>
                                    <button type="button" @click="cancelEdit" class="btn-cancel">Batal Edit</button>
                                    <button type="submit" class="btn-save btn-green" :disabled="form.processing">
                            <i :class="form.processing ? 'bx bx-loader-alt bx-spin' : 'bx bx-save'"></i> 
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Teleport Toast -->
    <Teleport to="body">
        <div :class="['toast-notification', toastType, { 'show': showToast }]">
            <i :class="toastType === 'success' ? 'bx bx-check-circle' : 'bx bx-error-circle'" class="toast-icon"></i>
            <span>{{ toastMessage }}</span>
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

/*  CARD */
.premium-card { background: var(--panel); border: 1px solid var(--line); border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.3); overflow: hidden; }
.card-header-accent { background: linear-gradient(90deg, rgba(16,21,31,1) 0%, rgba(20,27,41,1) 100%); padding: 24px 30px; border-bottom: 1px solid var(--line); border-top: 3px solid var(--accent); font-family: 'Sora', sans-serif; font-weight: 700; font-size: 16px; color: #fff; display: flex; align-items: center; gap: 10px; transition: 0.3s;}
.card-header-accent i { color: var(--accent); font-size: 22px; }
.card-body { padding: 40px; }

/* SIDE-BY-SIDE LAYOUT */
.layout-side-by-side { display: grid; grid-template-columns: 360px 1fr; gap: 40px; align-items: stretch; }
@media (max-width: 900px) { .layout-side-by-side { grid-template-columns: 1fr; } }
.section-label { display: block; font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 20px; font-family: 'Sora', sans-serif; border-bottom: 1px dashed var(--line); padding-bottom: 10px;}

/* UPLOAD ZONE */
.upload-zone { width: 100%; height: 100%; min-height: 350px; background: var(--bg); border: 2px dashed var(--line); border-radius: 16px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; cursor: pointer; transition: 0.3s;}
.upload-zone:hover:not(.disabled-zone) { border-color: var(--cyan); background: rgba(78, 155, 224, 0.05); }
.upload-zone.has-image { border-style: solid; border-color: var(--cyan); }
.preview-img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; z-index: 1;}
.preview-img.old.dimmed { opacity: 0.3; filter: grayscale(100%); }
.upload-prompt { text-align: center; color: var(--dim); transition: 0.3s; position: relative; z-index: 2; padding: 20px;}
.upload-zone:hover:not(.disabled-zone) .upload-prompt { color: var(--cyan); transform: scale(1.05); }
.icon-circle { width: 64px; height: 64px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px auto; backdrop-filter: blur(5px);}
.upload-prompt h4 { font-family: 'Sora', sans-serif; margin: 0 0 8px 0; color: #fff; font-size: 16px;}
.disabled-zone { cursor: not-allowed; border-color: transparent; background: transparent;}

/* FORMS */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.input-group { margin-bottom: 24px; }
.input-group label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: var(--dim); margin-bottom: 10px; }
.input-group input, .input-group textarea { width: 100%; background: var(--bg); border: 1px solid var(--line); color: #fff; padding: 14px 18px; border-radius: 12px; font-size: 14px; transition: 0.3s; box-sizing: border-box; font-family: 'Inter', sans-serif;}
.input-group input:focus, .input-group textarea:focus { outline: none; border-color: var(--cyan); background: var(--panel-2); box-shadow: 0 0 0 4px rgba(78, 155, 224, 0.1); }
.input-group textarea { resize: vertical; }
.input-bold { font-weight: 700; font-size: 15px !important; }

/* Disabled states */
input:disabled, textarea:disabled { background: rgba(255,255,255,0.02) !important; color: rgba(255,255,255,0.4) !important; border-color: transparent !important; cursor: not-allowed; }

.action-footer { display: flex; justify-content: flex-end; gap: 12px; border-top: 1px dashed var(--line); padding-top: 24px; }
.btn-cancel { padding: 12px 24px; background: transparent; border: 1px solid var(--line); color: var(--text); border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s; }
.btn-cancel:hover { background: rgba(255,255,255,0.05); }
.btn-save { padding: 12px 28px; background: var(--primary); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(55, 99, 224, 0.2); }
.btn-save:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(55, 99, 224, 0.4); background: #4676FA;}
.btn-green { background: #10B981; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2); }
.btn-green:hover:not(:disabled) { background: #059669; box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); }
</style>

<style>
/* Toast Global CSS */
.toast-notification { position: fixed; top: 30px; right: 30px; transform: translateY(-100px); opacity: 0; padding: 16px 24px; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); background: rgba(16, 21, 31, 0.95); backdrop-filter: blur(10px); display: flex; align-items: center; gap: 12px; font-family: 'Inter', sans-serif; font-size: 13.5px; font-weight: 600; z-index: 9999; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.toast-notification.show { transform: translateY(0); opacity: 1; }
.toast-notification.error { border: 1px solid #FF3B30; color: #FF3B30; }
.toast-notification.success { border: 1px solid #10B981; color: #10B981; }
.toast-icon { font-size: 24px; }
</style>