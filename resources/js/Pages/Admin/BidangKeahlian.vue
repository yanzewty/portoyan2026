<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    header: { type: Object, default: () => ({}) },
    skills: { type: Array, default: () => [] }
});

// ==========================================
// TOAST NOTIFIKASI (Pojok Kanan Atas)
// ==========================================
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success'); 

const displayToast = (msg, type = 'success') => {
    toastMessage.value = msg;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3500);
};

// ==========================================
// MODAL KONFIRMASI (Pop-up Tengah)
// ==========================================
const modal = ref({
    isOpen: false, title: '', message: '', icon: 'bx-question-mark', color: '#4E9BE0', confirmText: 'Ya', showCancel: true, onConfirm: null
});

const openModal = (title, message, icon, color, confirmText, onConfirm, showCancel = true) => {
    modal.value = { isOpen: true, title, message, icon, color, confirmText, onConfirm, showCancel };
};
const closeModal = () => modal.value.isOpen = false;
const confirmAction = () => { if (modal.value.onConfirm) modal.value.onConfirm(); closeModal(); };

// ==========================================
// DAFTAR IKON (Murni Boxicons)
// ==========================================
const iconList = [
    { val: 'bx bx-code-alt', name: '--- Default (Code) ---' }, 
    { val: 'bx bxl-html5', name: 'HTML5' }, 
    { val: 'bx bxl-css3', name: 'CSS3' },
    { val: 'bx bxl-javascript', name: 'JavaScript / JS' }, 
    { val: 'bx bxl-php', name: 'PHP' }, 
    { val: 'bx bxl-tailwind-css', name: 'Laravel / CSS' },
    { val: 'bx bxl-react', name: 'React' }, 
    { val: 'bx bxl-vuejs', name: 'Vue.js' }, 
    { val: 'bx bxl-nodejs', name: 'Node.js' },
    { val: 'bx bxl-python', name: 'Python' }, 
    { val: 'bx bxl-java', name: 'Java' }, 
    { val: 'bx bxl-figma', name: 'Figma' },
    { val: 'bx bxl-github', name: 'GitHub' }, 
    { val: 'bx bxl-wordpress', name: 'WordPress' }, 
    { val: 'bx bx-data', name: 'Database / SQL' },
    { val: 'bx bx-paint', name: 'UI / UX Design' }, 
    { val: 'bx bx-palette', name: 'Design / Poster' },
    { val: 'bx bx-wrench', name: 'Hardware / Troubleshoot' }, 
    { val: 'bx bx-server', name: 'Server / Jaringan' },
    { val: 'bx bx-microphone', name: 'Public Speaking' }, 
    { val: 'bx bx-group', name: 'Leadership / Organisasi' },
    { val: 'bx bx-file', name: 'Administrasi / Word' }, 
    { val: 'bx bx-joystick', name: 'Gaming / E-Sport' }
];

const defaultSkills = [
    { name: 'HTML 5', icon: 'bx bxl-html5', color: '#E34F26' }, 
    { name: 'CSS 3', icon: 'bx bxl-css3', color: '#1572B6' },
    { name: 'JavaScript', icon: 'bx bxl-javascript', color: '#F7DF1E' }, 
    { name: 'Database', icon: 'bx bx-data', color: '#FF2D20' },
    { name: 'Vue JS', icon: 'bx bxl-vuejs', color: '#4FC08D' }, 
    { name: 'PHP', icon: 'bx bxl-php', color: '#777BB4' }
];

// ==========================================
// AUTO-TRANSLATE IKON LAMA KE BARU
// ==========================================
const autoMigrateIcon = (oldIcon, skillName) => {
    const name = (skillName || '').toLowerCase();
    const icon = (oldIcon || '').toLowerCase();

    // 1. Cek langsung dari nama (Paling Akurat)
    if (name.includes('html')) return 'bx bxl-html5';
    if (name.includes('css')) return 'bx bxl-css3';
    if (name.includes('javascript') || name === 'js') return 'bx bxl-javascript';
    if (name.includes('laravel')) return 'bx bxl-tailwind-css';
    if (name.includes('vue')) return 'bx bxl-vuejs';
    if (name.includes('php')) return 'bx bxl-php';
    if (name.includes('react')) return 'bx bxl-react';
    if (name.includes('node')) return 'bx bxl-nodejs';
    if (name.includes('python')) return 'bx bxl-python';
    if (name.includes('java') && !name.includes('script')) return 'bx bxl-java';
    if (name.includes('figma')) return 'bx bxl-figma';
    if (name.includes('github') || name.includes('git')) return 'bx bxl-github';
    if (name.includes('wordpress')) return 'bx bxl-wordpress';
    if (name.includes('data') || name.includes('sql')) return 'bx bx-data';
    if (name.includes('ui') || name.includes('ux') || name.includes('design')) return 'bx bx-paint';

    // 2. Kalau dari nama lolos, cek dari class ikon lama
    if (icon.includes('html5')) return 'bx bxl-html5';
    if (icon.includes('css3')) return 'bx bxl-css3';
    if (icon.includes('js') || icon.includes('javascript')) return 'bx bxl-javascript';
    if (icon.includes('laravel')) return 'bx bxl-tailwind-css';
    if (icon.includes('vue')) return 'bx bxl-vuejs';
    if (icon.includes('php')) return 'bx bxl-php';
    if (icon.includes('database')) return 'bx bx-data';

    // 3. Kalau ikon sudah ada di list Boxicons, biarkan saja
    const isAlreadyValid = iconList.some(i => i.val === oldIcon);
    if (isAlreadyValid) return oldIcon;

    // 4. Mentok, kasih default
    return 'bx bx-code-alt';
};

// Ngecek isi props dari backend dan auto-migrate jika ada ikon jadul
const getInitialSkills = () => {
    let initial = props.skills && props.skills.length > 0 ? props.skills : defaultSkills;
    let parsed = JSON.parse(JSON.stringify(initial));
    
    // Looping data, ubah semua ikon jadul ke Boxicons otomatis
    parsed.forEach(item => {
        item.icon = autoMigrateIcon(item.icon, item.name);
    });

    return parsed;
};

// State form Inertia
const form = useForm({
    skill_tag: props.header?.tag || '03 / KEAHLIAN SINGKAT',
    skill_title: props.header?.title || 'Keahlian Singkat Saya',
    skill_desc: props.header?.desc || 'Memadukan kemampuan teknis IT dengan tata kelola organisasi yang rapi.',
    skills_data: getInitialSkills()
});

// ==========================================
// TIMER PERINGATAN (Ilang Sendiri 5 Detik)
// ==========================================
const showUnsavedWarning = ref(false);
let warningTimer = null;

watch(() => form.isDirty, (isDirtyNow) => {
    if (isDirtyNow) {
        showUnsavedWarning.value = true;
        if (warningTimer) clearTimeout(warningTimer);
        warningTimer = setTimeout(() => {
            showUnsavedWarning.value = false;
        }, 5000);
    } else {
        showUnsavedWarning.value = false;
    }
});

// ==========================================
// LOGIKA MODE PILIH (Centang Banyak)
// ==========================================
const isSelectMode = ref(false); 
const selectedIndices = ref([]); 

const toggleSelectMode = () => { isSelectMode.value = true; };
const cancelSelectMode = () => { isSelectMode.value = false; selectedIndices.value = []; };

const toggleSelection = (index) => {
    const pos = selectedIndices.value.indexOf(index);
    if (pos === -1) selectedIndices.value.push(index);
    else selectedIndices.value.splice(pos, 1);
};

const handleRowClick = (index, event) => {
    if (!isSelectMode.value) return;
    const tag = event.target.tagName;
    if (tag === 'INPUT' || tag === 'SELECT' || tag === 'OPTION') return;
    toggleSelection(index);
};

const isAllSelected = computed(() => form.skills_data.length > 0 && selectedIndices.value.length === form.skills_data.length);

const toggleSelectAll = () => {
    if (isAllSelected.value) selectedIndices.value = [];
    else selectedIndices.value = form.skills_data.map((_, idx) => idx);
};

// ==========================================
// LOGIKA SLIDE PAGINATION
// ==========================================
const itemsPerPage = 5; 
const currentPage = ref(0);

const totalPages = computed(() => Math.max(1, Math.ceil(form.skills_data.length / itemsPerPage)));

const paginatedSkills = computed(() => {
    const start = currentPage.value * itemsPerPage;
    return form.skills_data
        .map((skill, idx) => ({ skill, idx }))
        .slice(start, start + itemsPerPage);
});

const goToPage = (n) => {
    if (n >= 0 && n < totalPages.value) currentPage.value = n;
};
const prevPage = () => goToPage(currentPage.value - 1);
const nextPage = () => goToPage(currentPage.value + 1);

const clampCurrentPage = () => {
    if (currentPage.value > totalPages.value - 1) {
        currentPage.value = Math.max(0, totalPages.value - 1);
    }
};

// ==========================================
// FUNGSI AKSI FORM
// ==========================================
const addSkillRow = () => {
    form.skills_data.push({ name: '', icon: 'bx bx-code-alt', color: '#4E9BE0' });
    currentPage.value = totalPages.value - 1;
};

const removeSkillRow = (index) => {
    openModal('Hapus Keahlian', 'Yakin ingin menghapus item ini?', 'bx-trash', '#FF3B30', 'Ya, Hapus', 
        () => { 
            form.skills_data.splice(index, 1); 
            clampCurrentPage();
        }, true);
};

const removeSelectedSkills = () => {
    openModal('Hapus Terpilih', `Yakin ingin menghapus ${selectedIndices.value.length} keahlian yang dicentang?`, 'bx-trash-alt', '#FF3B30', 'Ya, Hapus Terpilih', 
        () => { 
            form.skills_data = form.skills_data.filter((_, index) => !selectedIndices.value.includes(index)); 
            cancelSelectMode();
            clampCurrentPage();
            displayToast('Item terpilih dihapus dari draf.', 'error');
        }, true);
};

const cancelForm = () => {
    openModal('Batalkan Perubahan', 'Yakin membatalkan? Semua perubahan akan dikembalikan seperti semula.', 'bx-x-circle', '#FF3B30', 'Ya, Batalkan', 
        () => { 
            form.reset(); 
            form.skills_data = getInitialSkills(); 
            cancelSelectMode(); 
            currentPage.value = 0;
            displayToast('Perubahan dibatalkan.', 'error'); 
        }, true);
};

const submit = () => {
    if (!form.isDirty) {
        openModal('Belum Ada Perubahan', 'Kamu belum mengubah atau menambah data apapun.', 'bx-info-circle', '#8792A6', 'Kembali', null, false);
    } else {
        openModal('Konfirmasi Simpan', 'Yakin ingin menyimpan semua perubahan?', 'bx-save', '#4E9BE0', 'Ya, Simpan', 
            () => {
                form.post('/admin/bidang-keahlian/update', {
                    preserveScroll: true,
                    onSuccess: () => { cancelSelectMode(); displayToast('Perubahan berhasil disimpan!', 'success'); },
                    onError: () => displayToast('Gagal menyimpan! Periksa form isian.', 'error')
                });
            }, true);
    }
};
</script>

<template>
  <Head title="Kelola Bidang Keahlian" />

  <div class="admin-container">
    <div class="wrap-form">

      <!-- HEADER HALAMAN -->
      <div class="page-header">
        <div class="header-content">
          <Link href="/admin" class="btn-back"><i class='bx bx-arrow-back'></i> Kembali ke Dashboard</Link>
          <h1>Bidang Keahlian Singkat</h1>
          <p>Kelola daftar bidang keahlian dan ikon teknismu.</p>
        </div>
        <a href="/#keahlian-singkat" target="_blank" class="btn-outline-glow">
          <i class='bx bx-link-external'></i> Lihat Website
        </a>
      </div>

      <form @submit.prevent="submit">

        <!-- BAGIAN 1: FORM TEKS UTAMA HEADER -->
        <div class="premium-card">
          <div class="card-header-accent" style="--accent: var(--cyan);">
            <i class='bx bx-text'></i> Teks Utama
          </div>
          <div class="card-body">
            <div class="grid-2">
              <div class="input-group">
                <label>Tag mini</label>
                <input type="text" v-model="form.skill_tag" required placeholder="03 / KEAHLIAN SINGKAT">
              </div>
              <div class="input-group">
                <label>Judul Utama</label>
                <input type="text" v-model="form.skill_title" required placeholder="Keahlian Singkat Saya">
              </div>
            </div>
            <div class="input-group">
              <label>Deskripsi Singkat</label>
              <textarea v-model="form.skill_desc" rows="2" required placeholder="Deskripsi mengenai keahlian..."></textarea>
            </div>
          </div>
        </div>

        <!-- BAGIAN 2: DAFTAR KEAHLIAN (MODE SLIDE) -->
        <div class="premium-card" style="margin-top: 40px;">
          <div class="card-header-accent" style="--accent: #10B981;">
            <i class='bx bx-category'></i> Daftar Keahlian & Ikon
          </div>

          <div class="card-body">
            
            <!-- HEADER KONTROL KEAHLIAN (TOTAL ITEM & PILIH) -->
            <div class="select-mode-header">
              <div class="skill-count">
                TOTAL: {{ form.skills_data.length }} ITEM KEAHLIAN
                <span v-if="totalPages > 1" class="skill-count-page"> &bull; Halaman {{ currentPage + 1 }} / {{ totalPages }}</span>
              </div>
              <button v-if="!isSelectMode && form.skills_data.length > 0" type="button" class="btn-toggle-select" @click="toggleSelectMode">
                <i class='bx bx-check-square'></i> Pilih
              </button>
            </div>

            <!-- PANEL MENU MODE CENTANG / BULK ACTION -->
            <div v-if="isSelectMode && form.skills_data.length > 0" class="bulk-action-bar">
              <div class="left-bulk">
                <label class="custom-checkbox">
                  <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll">
                  <span class="checkmark"></span>
                  <span>Pilih Semua ({{ selectedIndices.length }})</span>
                </label>
              </div>
              <div class="right-bulk">
                <button type="button" class="btn-cancel-select" @click="cancelSelectMode">Batal Mode</button>
                <button v-if="selectedIndices.length > 0" type="button" class="btn-bulk-delete" @click="removeSelectedSkills">
                  <i class='bx bx-trash'></i> Hapus Terpilih
                </button>
              </div>
            </div>

            <!-- JIKA DATA KOSONG -->
            <div v-if="form.skills_data.length === 0" class="empty-state-box">
              <i class='bx bx-folder-open'></i>
              <p>Belum ada data keahlian. Klik <strong>Tambah Keahlian Baru</strong> di bawah.</p>
            </div>

            <!-- TABEL DAFTAR KEAHLIAN -->
            <div v-else class="slide-viewport">
              <div class="skills-list-header">
                <span class="col-spacer"></span>
                <span>Nama Keahlian</span>
                <span>Ikon</span>
                <span>Warna</span>
                <span class="col-spacer"></span>
              </div>
              
              <div id="skills-container" class="skills-list">
                <div v-for="item in paginatedSkills" :key="item.idx" class="skill-row-item" :class="{'row-selected': selectedIndices.includes(item.idx), 'is-selectable': isSelectMode}" @click="handleRowClick(item.idx, $event)">

                  <label v-if="isSelectMode" class="custom-checkbox checkbox-card">
                    <input type="checkbox" :checked="selectedIndices.includes(item.idx)" @change="toggleSelection(item.idx)">
                    <span class="checkmark"></span>
                  </label>

                  <!-- Preview Ikon Kiri -->
                  <div class="icon-preview-sm" :style="{ background: form.skills_data[item.idx].color + '15', color: form.skills_data[item.idx].color, border: '1px solid ' + form.skills_data[item.idx].color + '40' }">
                    <i :class="form.skills_data[item.idx].icon"></i>
                  </div>

                  <!-- Input Nama -->
                  <input type="text" class="skill-input-name" v-model="form.skills_data[item.idx].name" required placeholder="Contoh: Laravel">

                  <!-- Select Dropdown Ikon (Sudah rapi karena data lama dimigrasi otomatis) -->
                  <div class="skill-select-wrap">
                    <select v-model="form.skills_data[item.idx].icon">
                      <option v-for="icon in iconList" :key="icon.val" :value="icon.val">{{ icon.name }}</option>
                    </select>
                    <i class="bx bx-chevron-down custom-arrow-sm"></i>
                  </div>

                  <!-- Input Warna -->
                  <div class="skill-color-wrap" title="Klik untuk ganti warna">
                    <div class="color-dot-sm" :style="{ background: form.skills_data[item.idx].color }"></div>
                    <span class="color-hex-sm">{{ form.skills_data[item.idx].color }}</span>
                    <input type="color" v-model="form.skills_data[item.idx].color" class="hidden-color-picker">
                  </div>

                  <!-- Tombol Hapus Baris -->
                  <button v-if="!isSelectMode" type="button" class="btn-remove-skill-sm" @click="removeSkillRow(item.idx)" title="Hapus Baris Ini">
                    <i class='bx bx-trash'></i>
                  </button>
                </div>
              </div>

              <!-- NAVIGASI SLIDE -->
              <div v-if="totalPages > 1" class="slide-nav">
                <button type="button" class="btn-slide-nav" :disabled="currentPage === 0" @click="prevPage">
                  <i class='bx bx-chevron-left'></i> Sebelumnya
                </button>
                <div class="slide-dots">
                  <span v-for="n in totalPages" :key="n" class="dot" :class="{ active: (n - 1) === currentPage }" @click="goToPage(n - 1)"></span>
                </div>
                <button type="button" class="btn-slide-nav" :disabled="currentPage === totalPages - 1" @click="nextPage">
                  Selanjutnya <i class='bx bx-chevron-right'></i>
                </button>
              </div>
            </div>

            <!-- TOMBOL TAMBAH BARIS BARU -->
            <button v-if="!isSelectMode" type="button" class="btn-add-dashed" @click="addSkillRow">
              <i class="bx bx-plus-circle"></i> Tambah Keahlian Baru
            </button>

            <!-- FOOTER SIMPAN / BATAL -->
            <div class="action-footer">
              <button v-if="form.isDirty" type="button" class="btn-cancel" @click="cancelForm">Batal Perubahan</button>
              <button type="submit" class="btn-save" :disabled="form.processing">
                <i :class="form.processing ? 'bx bx-loader-alt bx-spin' : 'bx bx-save'"></i> 
                {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- NOTIFIKASI WARNING MELAYANG -->
  <Teleport to="body">
    <transition name="slide-down">
      <div v-if="showUnsavedWarning" class="unsaved-warning-bar">
        <div class="warning-icon-wrap"><i class='bx bx-error bx-tada'></i></div>
        <div class="warning-text">
          <strong>Ada perubahan yang belum disimpan!</strong>
          <span>Jangan lupa klik tombol "Simpan Perubahan" di bawah.</span>
        </div>
      </div>
    </transition>
  </Teleport>

  <!-- MODAL KONFIRMASI & TOAST -->
  <Teleport to="body">
    <div :class="['toast-notification', toastType, { 'show': showToast }]">
      <i :class="toastType === 'success' ? 'bx bx-check-circle' : 'bx bx-error-circle'" class="toast-icon"></i>
      <span>{{ toastMessage }}</span>
    </div>
    
    <div v-if="modal.isOpen" class="modal-overlay" @click="closeModal"></div>
    <div v-if="modal.isOpen" class="custom-modal">
      <div class="modal-icon" :style="{ color: modal.color, background: modal.color + '1A' }">
        <i :class="'bx ' + modal.icon"></i>
      </div>
      <h3 class="modal-title">{{ modal.title }}</h3>
      <p class="modal-desc">{{ modal.message }}</p>
      <div class="modal-actions">
        <button v-if="modal.showCancel" type="button" class="btn-modal-cancel" @click="closeModal">Batal</button>
        <button type="button" class="btn-modal-confirm" :style="{ background: modal.color }" @click="modal.onConfirm ? confirmAction() : closeModal()">
          {{ modal.confirmText }}
        </button>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* 
======================================================
1. VARIABEL & SETTINGAN DASAR KANVAS
======================================================
*/
.admin-container {
    --bg: #0A0E17;
    --panel: #10151F;
    --panel-2: #141B29;
    --line: #232D3E;
    --text: #EAEEF5;
    --dim: #8792A6;
    --cyan: #4E9BE0;
    --primary: #3763E0;
    --danger: #FF3B30;
    
    background-color: var(--bg);
    color: var(--text);
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    padding: 40px;
    box-sizing: border-box;
}

/* Membatasi lebar form agar tidak melar sampai ujung */
.wrap-form {
    max-width: 1050px;
    margin: 0 auto;
    padding-bottom: 60px;
}

/* 
======================================================
2. HEADER (Judul Halaman & Tombol Kembali)
======================================================
*/
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 40px;
    border-bottom: 1px solid var(--line);
    padding-bottom: 20px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--dim);
    font-size: 13.5px;
    text-decoration: none;
    margin-bottom: 12px;
    transition: 0.3s;
}

.btn-back:hover {
    color: var(--cyan);
    transform: translateX(-4px);
}

.header-content h1 {
    font-family: 'Sora', sans-serif;
    font-size: 30px;
    font-weight: 800;
    color: #fff;
    margin: 0 0 5px 0;
}

.header-content p {
    color: var(--dim);
    font-size: 14px;
    margin: 0;
}

/* Tombol ke halaman utama web */
.btn-outline-glow {
    padding: 10px 20px;
    border: 1px solid var(--line);
    border-radius: 10px;
    font-size: 13px;
    color: var(--text);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: 0.3s;
    background: var(--panel);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.btn-outline-glow:hover {
    border-color: var(--cyan);
    color: var(--cyan);
    background: rgba(78, 155, 224, 0.05);
    box-shadow: 0 0 15px rgba(78, 155, 224, 0.2);
}

/* 
======================================================
3. BUNGKUSAN KARTU UTAMA (CARD)
======================================================
*/
.premium-card {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
    overflow: hidden;
}

/* Aksen Garis Warna di Atas Kartu */
.card-header-accent {
    background: linear-gradient(90deg, rgba(16,21,31,1) 0%, rgba(20,27,41,1) 100%);
    padding: 24px 30px;
    border-bottom: 1px solid var(--line);
    border-top: 3px solid var(--accent);
    font-family: 'Sora', sans-serif;
    font-weight: 700;
    font-size: 16px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-header-accent i {
    color: var(--accent);
    font-size: 22px;
}

.card-body {
    padding: 35px;
}

/* 
======================================================
4. INPUT FORM BESAR (Untuk Teks Utama Atas)
======================================================
*/
.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.input-group {
    margin-bottom: 24px;
}

.input-group label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--dim);
    margin-bottom: 10px;
}

.input-group input, 
.input-group textarea, 
.input-group select {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--line);
    color: #fff;
    padding: 14px 18px;
    border-radius: 12px;
    font-size: 14px;
    transition: 0.3s;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

.input-group input:focus, 
.input-group textarea:focus, 
.input-group select:focus {
    outline: none;
    border-color: var(--cyan);
    background: var(--panel-2);
    box-shadow: 0 0 0 4px rgba(78, 155, 224, 0.1);
}

.input-group textarea {
    resize: vertical;
}

/* 
======================================================
5. TOMBOL FOOTER (Simpan & Batal)
======================================================
*/
.action-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px dashed var(--line);
    margin-top: 35px;
    padding-top: 24px;
}

.btn-cancel {
    padding: 14px 28px;
    background: transparent;
    border: 1px solid var(--line);
    color: var(--text);
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.btn-cancel:hover {
    background: rgba(255,255,255,0.05);
}

.btn-save {
    padding: 14px 32px;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(55, 99, 224, 0.2);
    font-size: 14px;
}

.btn-save:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(55, 99, 224, 0.4);
    background: #4676FA;
}

.btn-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* 
======================================================
6. TOMBOL TAMBAH BARIS BARU 
======================================================
*/
.btn-add-dashed {
    width: 100%;
    padding: 16px;
    background: transparent;
    border: 2px dashed var(--line);
    color: var(--dim);
    border-radius: 14px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: 0.3s;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 20px;
}

.btn-add-dashed:hover {
    border-color: var(--cyan);
    color: var(--cyan);
    background: rgba(78, 155, 224, 0.05);
}

/* 
======================================================
7. HEADER MODE PILIH & JUMLAH DATA
======================================================
*/
.select-mode-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px dashed var(--line);
}

.skill-count {
    font-size: 11px;
    font-family: 'JetBrains Mono', monospace;
    color: var(--dim);
    font-weight: 700;
    letter-spacing: 1px;
}

.skill-count-page {
    color: var(--cyan);
}

.btn-toggle-select { 
    background: rgba(255,255,255,0.03); 
    border: 1px solid var(--line); 
    color: var(--text); 
    padding: 8px 16px; 
    border-radius: 8px; 
    font-size: 12px; 
    font-weight: 600; 
    cursor: pointer; 
    display: flex; 
    align-items: center; 
    gap: 6px; 
    transition: 0.3s; 
}

.btn-toggle-select:hover { 
    background: rgba(78, 155, 224, 0.1); 
    border-color: var(--cyan); 
    color: var(--cyan); 
}

/* 
======================================================
8. BAR ALAT BULK ACTION (Hapus Banyak)
======================================================
*/
.bulk-action-bar { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    background: rgba(78, 155, 224, 0.05); 
    padding: 16px 20px; 
    border-radius: 12px; 
    border: 1px solid var(--cyan); 
    margin-bottom: 24px; 
    animation: fadeIn 0.3s ease; 
}

.left-bulk, 
.right-bulk { 
    display: flex; 
    align-items: center; 
    gap: 16px; 
}

/* Kotak Centang Kustomisasi */
.custom-checkbox { 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    cursor: pointer; 
    font-size: 13.5px; 
    color: var(--text); 
    font-weight: 600; 
    user-select: none; 
}

.custom-checkbox input { 
    position: absolute; 
    opacity: 0; 
    cursor: pointer; 
    height: 0; 
    width: 0; 
}

.checkmark { 
    height: 22px; 
    width: 22px; 
    background-color: var(--bg); 
    border: 2px solid var(--line); 
    border-radius: 6px; 
    transition: 0.2s; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
}

.custom-checkbox:hover input ~ .checkmark { 
    border-color: var(--cyan); 
}

.custom-checkbox input:checked ~ .checkmark { 
    background-color: var(--cyan); 
    border-color: var(--cyan); 
}

.checkmark:after { 
    content: ""; 
    display: none; 
    width: 5px; 
    height: 10px; 
    border: solid white; 
    border-width: 0 2px 2px 0; 
    transform: rotate(45deg); 
    margin-bottom: 2px; 
}

.custom-checkbox input:checked ~ .checkmark:after { 
    display: block; 
}

.checkbox-card { 
    margin: 0; 
}

.btn-bulk-delete { 
    background: rgba(255, 59, 48, 0.1); 
    color: var(--danger); 
    border: 1px solid rgba(255, 59, 48, 0.3); 
    padding: 10px 16px; 
    border-radius: 10px; 
    font-size: 13px; 
    font-weight: 600; 
    cursor: pointer; 
    transition: 0.2s; 
    display: flex; 
    align-items: center; 
    gap: 6px; 
}

.btn-bulk-delete:hover { 
    background: var(--danger); 
    color: #fff; 
    border-color: var(--danger); 
}

.btn-cancel-select { 
    background: transparent; 
    color: var(--dim); 
    border: none; 
    font-size: 13px; 
    font-weight: 600; 
    padding: 10px 14px; 
    cursor: pointer; 
    transition: 0.2s; 
}

.btn-cancel-select:hover { 
    color: #fff; 
}

/* 
======================================================
9. DAFTAR BARIS TABEL (Item Keahlian)
======================================================
*/
.slide-viewport { 
    animation: fadeIn 0.35s ease; 
}

.skills-list-header { 
    display: flex; 
    align-items: center; 
    gap: 14px; 
    padding: 0 14px 10px; 
    font-size: 10.5px; 
    font-weight: 700; 
    text-transform: uppercase; 
    letter-spacing: 1px; 
    color: var(--dim); 
    border-bottom: 1px solid var(--line); 
    margin-bottom: 4px; 
}

.skills-list-header .col-spacer { 
    width: 38px; 
    flex-shrink: 0; 
}

.skills-list-header span:nth-child(2) { 
    flex: 1.6; 
}

.skills-list-header span:nth-child(3) { 
    flex: 1.3; 
}

.skills-list-header span:nth-child(4) { 
    width: 130px; 
    flex-shrink: 0; 
}

@media (max-width: 760px) {
    .skills-list-header { 
        display: none; 
    }
}

.skills-list { 
    display: flex; 
    flex-direction: column; 
    gap: 8px; 
}

/* Styling Per Baris Item */
.skill-row-item { 
    display: flex; 
    align-items: center; 
    gap: 14px; 
    padding: 10px 14px; 
    border-radius: 10px; 
    border: 1px solid transparent; 
    background: rgba(255,255,255,0.015); 
    transition: 0.2s; 
    flex-wrap: wrap; 
}

.skill-row-item:hover { 
    background: rgba(255,255,255,0.03); 
    border-color: var(--line); 
}

.skill-row-item.row-selected { 
    border-color: var(--cyan); 
    background: rgba(78, 155, 224, 0.06); 
}

.skill-row-item.is-selectable { 
    cursor: pointer; 
}

.skill-row-item.is-selectable:hover { 
    border-color: var(--cyan); 
}

/* 
======================================================
10. KOMPONEN KECIL DI DALAM BARIS TABEL
======================================================
*/
/* Kotak Ikon di Kiri */
.icon-preview-sm { 
    width: 38px; 
    height: 38px; 
    border-radius: 10px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 17px; 
    flex-shrink: 0; 
}

/* Inputan Nama Keahlian */
.skill-input-name { 
    flex: 1.6; 
    min-width: 140px; 
    background: var(--bg); 
    border: 1px solid var(--line); 
    color: #fff; 
    padding: 10px 14px; 
    border-radius: 10px; 
    font-size: 13.5px; 
    font-family: 'Inter', sans-serif; 
    box-sizing: border-box; 
    transition: 0.2s; 
}

.skill-input-name:focus { 
    outline: none; 
    border-color: var(--cyan); 
    box-shadow: 0 0 0 3px rgba(78, 155, 224, 0.1); 
}

/* Dropdown Pilihan Ikon */
.skill-select-wrap { 
    position: relative; 
    flex: 1.3; 
    min-width: 130px; 
}

.skill-select-wrap select { 
    width: 100%; 
    appearance: none; 
    cursor: pointer; 
    background: var(--bg); 
    border: 1px solid var(--line); 
    color: #fff; 
    padding: 10px 32px 10px 14px; 
    border-radius: 10px; 
    font-size: 13.5px; 
    font-family: 'Inter', sans-serif; 
    box-sizing: border-box; 
    transition: 0.2s; 
}

.skill-select-wrap select:focus { 
    outline: none; 
    border-color: var(--cyan); 
}

/* Panah buatan sendiri biar keren */
.custom-arrow-sm { 
    position: absolute; 
    right: 12px; 
    top: 50%; 
    transform: translateY(-50%); 
    pointer-events: none; 
    color: var(--dim); 
    font-size: 11px; 
}

/* Input Color Picker */
.skill-color-wrap { 
    position: relative; 
    width: 130px; 
    height: 38px; 
    background: var(--bg); 
    border: 1px solid var(--line); 
    border-radius: 10px; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    padding: 0 12px; 
    cursor: pointer; 
    box-sizing: border-box; 
    flex-shrink: 0; 
    transition: 0.2s; 
}

.skill-color-wrap:hover { 
    border-color: var(--cyan); 
}

.color-dot-sm { 
    width: 16px; 
    height: 16px; 
    border-radius: 5px; 
    border: 1px solid rgba(255,255,255,0.2); 
    flex-shrink: 0; 
}

.color-hex-sm { 
    font-family: 'JetBrains Mono', monospace; 
    font-size: 11.5px; 
    color: var(--dim); 
    font-weight: 600; 
}

/* Sembunyikan color picker aslinya biar ga jelek */
.hidden-color-picker { 
    position: absolute; 
    inset: 0; 
    opacity: 0; 
    width: 100%; 
    height: 100%; 
    cursor: pointer; 
}

/* Tombol Hapus Satuan per baris */
.btn-remove-skill-sm { 
    background: transparent; 
    color: var(--danger); 
    border: 1px solid rgba(255, 59, 48, 0.2); 
    width: 36px; 
    height: 36px; 
    border-radius: 10px; 
    cursor: pointer; 
    transition: 0.2s; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 15px; 
    flex-shrink: 0; 
    margin-left: auto; 
}

.btn-remove-skill-sm:hover { 
    background: var(--danger); 
    color: #fff; 
    border-color: var(--danger); 
}

@media (max-width: 760px) {
    .btn-remove-skill-sm { 
        margin-left: 0; 
    }
}

/* 
======================================================
11. NAVIGASI SLIDE & DATA KOSONG
======================================================
*/
.slide-nav { 
    display: flex; 
    align-items: center; 
    justify-content: space-between; 
    gap: 16px; 
    margin-top: 24px; 
    padding-top: 20px; 
    border-top: 1px dashed var(--line); 
}

.btn-slide-nav { 
    background: rgba(255,255,255,0.03); 
    border: 1px solid var(--line); 
    color: var(--text); 
    padding: 10px 18px; 
    border-radius: 10px; 
    font-size: 13px; 
    font-weight: 600; 
    cursor: pointer; 
    display: flex; 
    align-items: center; 
    gap: 6px; 
    transition: 0.3s; 
}

.btn-slide-nav:hover:not(:disabled) { 
    background: rgba(78, 155, 224, 0.1); 
    border-color: var(--cyan); 
    color: var(--cyan); 
}

.btn-slide-nav:disabled { 
    opacity: 0.35; 
    cursor: not-allowed; 
}

.slide-dots { 
    display: flex; 
    align-items: center; 
    gap: 8px; 
}

.dot { 
    width: 9px; 
    height: 9px; 
    border-radius: 50%; 
    background: var(--line); 
    cursor: pointer; 
    transition: 0.3s; 
}

.dot:hover { 
    background: var(--dim); 
}

.dot.active { 
    background: var(--cyan); 
    width: 24px; 
    border-radius: 6px; 
}

/* Box info jika data kosong */
.empty-state-box { 
    text-align: center; 
    padding: 40px; 
    background: rgba(255,255,255,0.02); 
    border: 1px dashed var(--line); 
    border-radius: 16px; 
    color: var(--dim); 
    margin-bottom: 20px; 
}

.empty-state-box i { 
    font-size: 36px; 
    margin-bottom: 12px; 
    opacity: 0.5; 
}

.empty-state-box p { 
    margin: 0; 
    font-size: 14px; 
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<style>
/* 
======================================================
CSS GLOBAL UNTUK NOTIFIKASI MELAYANG (WARNING & TOAST)
Ditulis terpisah tanpa 'scoped' agar tampil sempurna.
======================================================
*/
.unsaved-warning-bar {
    position: fixed;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(245, 158, 11, 0.95);
    backdrop-filter: blur(8px);
    padding: 14px 24px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 10px 35px rgba(245, 158, 11, 0.4);
    z-index: 9999;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.warning-icon-wrap {
    background: rgba(255, 255, 255, 0.2);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
}

.warning-text {
    display: flex;
    flex-direction: column;
    color: #fff;
    font-family: 'Inter', sans-serif;
}

.warning-text strong {
    font-size: 14px;
    font-weight: 700;
    font-family: 'Sora', sans-serif;
}

.warning-text span {
    font-size: 12.5px;
    opacity: 0.9;
    margin-top: 2px;
}

/* Animasi Masuk/Keluar dari Atas untuk Warning Bar */
.slide-down-enter-active, 
.slide-down-leave-active {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.slide-down-enter-from, 
.slide-down-leave-to {
    opacity: 0;
    transform: translate(-50%, -50px);
}

.slide-down-enter-to, 
.slide-down-leave-from {
    opacity: 1;
    transform: translate(-50%, 0);
}

/* 
======================================================
TOAST NOTIFICATION & KOTAK MODAL KONFIRMASI
======================================================
*/
.toast-notification {
    position: fixed;
    top: 30px;
    right: 30px;
    transform: translateY(-100px);
    opacity: 0;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.5);
    background: rgba(16, 21, 31, 0.95);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: 12px;
    font-family: 'Inter', sans-serif;
    font-size: 13.5px;
    font-weight: 600;
    z-index: 9999;
    transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-notification.show { 
    transform: translateY(0); 
    opacity: 1; 
}

.toast-notification.error { 
    border: 1px solid #FF3B30; 
    color: #FF3B30; 
}

.toast-notification.success { 
    border: 1px solid #10B981; 
    color: #10B981; 
}

.toast-icon { 
    font-size: 24px; 
}

.modal-overlay { 
    position: fixed; 
    top: 0; 
    left: 0; 
    width: 100vw; 
    height: 100vh; 
    background: rgba(10, 14, 23, 0.85); 
    backdrop-filter: blur(6px); 
    z-index: 9998; 
}

.custom-modal { 
    position: fixed; 
    top: 50%; 
    left: 50%; 
    transform: translate(-50%, -50%); 
    background: #10151F; 
    border: 1px solid #232D3E; 
    border-radius: 20px; 
    padding: 35px 30px; 
    width: 90%; 
    max-width: 400px; 
    text-align: center; 
    z-index: 9999; 
    box-shadow: 0 25px 50px rgba(0,0,0,0.5); 
    animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
    font-family: 'Inter', sans-serif; 
}

@keyframes modalPop {
    0% { opacity: 0; transform: translate(-50%, -40%) scale(0.9); }
    100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
}

.modal-icon { 
    width: 65px; 
    height: 65px; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 32px; 
    margin: 0 auto 20px; 
}

.modal-title { 
    color: #fff; 
    font-family: 'Sora', sans-serif; 
    font-size: 20px; 
    font-weight: 700; 
    margin-bottom: 12px; 
}

.modal-desc { 
    color: #8792A6; 
    font-size: 14px; 
    line-height: 1.6; 
    margin-bottom: 30px; 
}

.modal-actions { 
    display: flex; 
    gap: 12px; 
    justify-content: center; 
}

.btn-modal-cancel { 
    flex: 1; 
    padding: 12px; 
    border-radius: 10px; 
    font-weight: 600; 
    cursor: pointer; 
    background: transparent; 
    color: #EAEEF5; 
    border: 1px solid #232D3E; 
    transition: 0.2s; 
}

.btn-modal-cancel:hover { 
    background: rgba(255,255,255,0.05); 
}

.btn-modal-confirm { 
    flex: 1; 
    padding: 12px; 
    border-radius: 10px; 
    font-weight: 600; 
    cursor: pointer; 
    color: #fff; 
    border: none; 
    transition: 0.2s; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
}

.btn-modal-confirm:hover { 
    filter: brightness(1.1); 
    transform: translateY(-2px); 
}
</style>