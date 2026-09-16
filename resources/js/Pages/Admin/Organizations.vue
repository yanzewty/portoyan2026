<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    profile: { type: Object, default: () => ({}) },
    header: { type: Object, default: () => ({}) },
    experiencesJson: { type: String, default: '[]' }
});

// ==========================================
// TOAST NOTIFIKASI (Muncul di Pojok Kanan Atas)
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
// MODAL KONFIRMASI (Pop-up peringatan di tengah)
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
// DATA BAWAAN & PARSING FORM
// ==========================================
// Data pancingan agar tidak kosong melompong saat database baru di-reset
const defaultExperiences = [
    { 
        posisi: 'Sekretaris Umum', 
        instansi: 'OSIS SMK Negeri 1 Surabaya', 
        periode: '2025 - Sekarang', 
        deskripsi: 'Bertanggung jawab penuh atas administrasi organisasi, tata kelola surat-menyurat resmi, serta melakukan koordinasi internal.' 
    },
    { 
        posisi: 'Ketua Karang Taruna', 
        instansi: 'Warga Setempat', 
        periode: '2023 - Sekarang', 
        deskripsi: 'Memimpin tim pemuda dalam merancang dan mengeksekusi kegiatan sosial kemasyarakatan.' 
    }
];

const getInitialData = () => {
    try {
        const parsed = JSON.parse(props.experiencesJson);
        if (Array.isArray(parsed) && parsed.length > 0) return parsed;
        return JSON.parse(JSON.stringify(defaultExperiences));
    } catch (e) {
        return JSON.parse(JSON.stringify(defaultExperiences));
    }
};

const form = useForm({
    org_tag: props.header?.tag || '04 / PENGALAMAN ORGANISASI',
    org_title: props.header?.title || 'Jejak Kepemimpinan',
    org_desc: props.header?.desc || 'Peran yang membentuk cara saya bekerja dalam tim dan mengambil keputusan.',
    org_data: getInitialData(), 
    experiences_data: '' // Wadah untuk menampung JSON string saat dilempar ke server
});

// ==========================================
// TIMER PERINGATAN (Peringatan simpan hilang sendiri 5 Detik)
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
// LOGIKA SLIDE / PAGINATION (Ditambah jadi 4 item per halaman)
// ==========================================
const itemsPerPage = 4; // Cocok untuk grid 2x2 (2 baris x 2 kolom)
const currentPage = ref(0);

const totalPages = computed(() => Math.max(1, Math.ceil(form.org_data.length / itemsPerPage)));

const paginatedData = computed(() => {
    const start = currentPage.value * itemsPerPage;
    return form.org_data
        .map((exp, index) => ({ exp, index }))
        .slice(start, start + itemsPerPage);
});

const goToPage = (n) => { if (n >= 0 && n < totalPages.value) currentPage.value = n; };
const prevPage = () => goToPage(currentPage.value - 1);
const nextPage = () => goToPage(currentPage.value + 1);

const clampCurrentPage = () => {
    if (currentPage.value > totalPages.value - 1) {
        currentPage.value = Math.max(0, totalPages.value - 1);
    }
};

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

const isAllSelected = computed(() => form.org_data.length > 0 && selectedIndices.value.length === form.org_data.length);

const toggleSelectAll = () => {
    if (isAllSelected.value) selectedIndices.value = [];
    else selectedIndices.value = form.org_data.map((_, idx) => idx);
};

// ==========================================
// FUNGSI AKSI (TAMBAH, HAPUS, BATAL, SIMPAN)
// ==========================================
const addRow = () => {
    form.org_data.push({ posisi: '', instansi: '', periode: '', deskripsi: '' });
    currentPage.value = totalPages.value - 1; // Otomatis ke slide terakhir
};

const removeRow = (index) => {
    openModal('Hapus Pengalaman', 'Yakin ingin menghapus jejak organisasi ini?', 'bx-trash', '#FF3B30', 'Ya, Hapus', 
        () => { 
            form.org_data.splice(index, 1); 
            clampCurrentPage(); 
        }, true);
};

const removeSelected = () => {
    openModal('Hapus Terpilih', `Yakin menghapus ${selectedIndices.value.length} pengalaman yang dicentang?`, 'bx-trash-alt', '#FF3B30', 'Ya, Hapus Terpilih', 
        () => { 
            form.org_data = form.org_data.filter((_, index) => !selectedIndices.value.includes(index)); 
            cancelSelectMode();
            clampCurrentPage();
            displayToast('Item dihapus dari draf.', 'error');
        }, true);
};

const cancelForm = () => {
    openModal('Batalkan Perubahan', 'Yakin ingin membatalkan? Semua editan akan dikembalikan ke awal.', 'bx-x-circle', '#FF3B30', 'Ya, Batalkan', 
        () => { 
            form.reset(); 
            form.org_data = getInitialData(); 
            cancelSelectMode(); 
            currentPage.value = 0;
            displayToast('Perubahan dibatalkan.', 'error'); 
        }, true);
};

const submit = () => {
    if (!form.isDirty) {
        openModal('Belum Ada Perubahan', 'Kamu belum mengubah atau menambah apapun.', 'bx-info-circle', '#8792A6', 'Kembali', null, false);
    } else {
        openModal('Konfirmasi Simpan', 'Yakin ingin Menyimpan?', 'bx-save', '#4E9BE0', 'Ya, Simpan', 
            () => {
                form.transform((data) => ({
                    ...data,
                    experiences_data: JSON.stringify(data.org_data) 
                })).post('/admin/organizations', {
                    preserveScroll: true,
                    onSuccess: () => {
                        cancelSelectMode();
                        displayToast('Perubahan berhasil disimpan!', 'success');
                    },
                    onError: () => displayToast('Gagal menyimpan! Periksa isian form.', 'error')
                });
            }, true);
    }
};
</script>

<template>
    <Head title="Kelola Jejak Organisasi" />

    <div class="admin-container">
        <div class="wrap-form">
            
            <!-- HEADER HALAMAN -->
            <div class="page-header">
                <div class="header-content">
                    <Link href="/admin" class="btn-back"><i class='bx bx-arrow-back'></i> Kembali ke Dashboard</Link>
                    <h1>Jejak Organisasi</h1>
                    <p>Kelola riwayat kepemimpinan dan timeline pengalamanmu.</p>
                </div>
                <a href="/#pengalaman-organisasi" target="_blank" class="btn-outline-glow">
                    <i class='bx bx-link-external'></i> Lihat Website
                </a>
            </div>

            <form @submit.prevent="submit">
                
                <!-- BAGIAN 1: FORM TEKS UTAMA -->
                <div class="premium-card">
                    <div class="card-header-accent" style="--accent: var(--cyan);">
                        <i class='bx bx-text'></i> Teks Utama
                    </div>
                    <div class="card-body">
                        <div class="grid-2-custom">
                            <div class="left-col">
                                <div class="input-group">
                                    <label>Tag / Sub-Judul (Kiri Atas)</label>
                                    <input type="text" v-model="form.org_tag" required placeholder="04 / PENGALAMAN ORGANISASI">
                                </div>
                                <div class="input-group" style="margin-bottom: 0;">
                                    <label>Judul Utama</label>
                                    <input type="text" v-model="form.org_title" required placeholder="Jejak Kepemimpinan">
                                </div>
                            </div>
                            <div class="right-col">
                                <div class="input-group" style="margin-bottom: 0; height: 100%;">
                                    <label>Deskripsi (Sebelah Kanan)</label>
                                    <textarea v-model="form.org_desc" required placeholder="Peran yang membentuk cara saya bekerja..." style="height: calc(100% - 25px);"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 2: DAFTAR PENGALAMAN (GRID & PAGINATION) -->
                <div class="premium-card" style="margin-top: 40px;">
                    <div class="card-header-accent" style="--accent: #34C77B;">
                        <i class='bx bx-network-chart'></i> Daftar Pengalaman & Timeline
                    </div>
                    
                    <div class="card-body">
                        <!-- HEADER KONTROL ITEM (Menampilkan jumlah slide) -->
                        <div class="select-mode-header">
                            <div class="skill-count">
                                TOTAL: {{ form.org_data.length }} PENGALAMAN 
                                <span v-if="totalPages > 1" class="page-indicator"> &bull; Slide {{ currentPage + 1 }} / {{ totalPages }}</span>
                            </div>
                            
                            <button v-if="!isSelectMode && form.org_data.length > 0" type="button" class="btn-toggle-select" @click="toggleSelectMode">
                                <i class='bx bx-check-square'></i> Select Mode
                            </button>
                        </div>

                        <!-- BAR MENU BULK ACTION (Pilih & Hapus Banyak) -->
                        <div v-if="isSelectMode && form.org_data.length > 0" class="bulk-action-bar">
                            <div class="left-bulk">
                                <label class="custom-checkbox">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll">
                                    <span class="checkmark"></span>
                                    <span>Pilih Semua ({{ selectedIndices.length }})</span>
                                </label>
                            </div>
                            <div class="right-bulk">
                                <button type="button" class="btn-cancel-select" @click="cancelSelectMode">Batal Mode</button>
                                <button v-if="selectedIndices.length > 0" type="button" class="btn-bulk-delete" @click="removeSelected">
                                    <i class='bx bx-trash'></i> Hapus Terpilih
                                </button>
                            </div>
                        </div>

                        <!-- STATE JIKA DATA KOSONG -->
                        <div v-if="form.org_data.length === 0" class="empty-state-box">
                            <i class='bx bx-folder-open'></i>
                            <p>Daftar pengalaman kosong. Klik <strong>Tambah Pengalaman Baru</strong> di bawah.</p>
                        </div>

                        <!-- KONTINER KARTU (Sekarang pakai Grid 2 Kolom menyamping) -->
                        <div v-else class="exp-grid-container">
                            <div v-for="item in paginatedData" :key="item.index" class="exp-card-item" :class="{'row-selected': selectedIndices.includes(item.index)}">
                                
                                <div class="exp-card-header">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <label v-if="isSelectMode" class="custom-checkbox checkbox-card">
                                            <input type="checkbox" :checked="selectedIndices.includes(item.index)" @change="toggleSelection(item.index)">
                                            <span class="checkmark"></span>
                                        </label>
                                        <span class="exp-number">PENGALAMAN #{{ item.index + 1 }}</span>
                                    </div>
                                    <button v-if="!isSelectMode" type="button" class="btn-remove-skill-sm" @click="removeRow(item.index)" title="Hapus Baris Ini">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>

                                <div class="exp-card-body">
                                    <div class="grid-2-inner">
                                        <div class="card-input-group">
                                            <label>Posisi / Jabatan</label>
                                            <input type="text" v-model="item.exp.posisi" placeholder="Ex: Sekretaris" required>
                                        </div>
                                        <div class="card-input-group">
                                            <label>Periode Waktu</label>
                                            <input type="text" v-model="item.exp.periode" placeholder="Ex: 2024 - Skrg" required>
                                        </div>
                                    </div>
                                    <div class="card-input-group">
                                        <label>Instansi / Organisasi / Lingkup</label>
                                        <input type="text" v-model="item.exp.instansi" placeholder="Ex: OSIS SMK Negeri 1 Surabaya" required>
                                    </div>
                                    <div class="card-input-group" style="margin-bottom: 0;">
                                        <label>Deskripsi Tugas & Pencapaian</label>
                                        <textarea v-model="item.exp.deskripsi" rows="3" placeholder="Bertanggung jawab atas..." required></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KONTROL NAVIGASI SLIDE -->
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

                        <!-- TOMBOL TAMBAH DATA BARU -->
                        <button v-if="!isSelectMode" type="button" class="btn-add-dashed" @click="addRow">
                            <i class="bx bx-plus-circle"></i> Tambah Pengalaman Baru
                        </button>

                        <!-- FOOTER ACTION (Simpan / Batal Utama) -->
                        <div class="action-footer">
                            <button v-if="form.isDirty" type="button" class="btn-cancel" @click="cancelForm">Batal Perubahan</button>
                            <button type="submit" class="btn-save" :disabled="form.processing">
                                <i :class="form.processing ? 'bx bx-loader-alt bx-spin' : 'bx bx-save'"></i> 
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Semua Perubahan' }}
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
                    <span>Jangan lupa klik tombol "Simpan Semua Perubahan" di bawah.</span>
                </div>
            </div>
        </transition>
    </Teleport>

    <!-- TOAST & MODAL CONFIRMATION -->
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

/* Biar form ga molor sampai ujung layar */
.wrap-form { 
    max-width: 1100px; /* Diperlebar dikit biar 2 kolomnya lega */
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

/* Tombol Lihat Website */
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
4. INPUT FORM BESAR (Untuk Teks Utama)
======================================================
*/
.grid-2-custom { 
    display: grid; 
    grid-template-columns: 1fr 1.2fr; 
    gap: 24px; 
    align-items: stretch;
}

@media (max-width: 768px) { 
    .grid-2-custom { grid-template-columns: 1fr; } 
}

.input-group { 
    margin-bottom: 24px; 
    display: flex; 
    flex-direction: column;
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
.input-group textarea { 
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
.input-group textarea:focus { 
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
5. TOMBOL FOOTER (Simpan & Batal Bawah)
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
6. BULK ACTION & MODE PILIH (Bagian Atas Tabel)
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

.page-indicator { 
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

/* Bar Hapus Biru */
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

.left-bulk, .right-bulk { 
    display: flex; 
    align-items: center; 
    gap: 16px; 
}

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
.custom-checkbox input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
.checkmark { height: 22px; width: 22px; background-color: var(--bg); border: 2px solid var(--line); border-radius: 6px; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
.custom-checkbox:hover input ~ .checkmark { border-color: var(--cyan); }
.custom-checkbox input:checked ~ .checkmark { background-color: var(--cyan); border-color: var(--cyan); }
.checkmark:after { content: ""; display: none; width: 5px; height: 10px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); margin-bottom: 2px; }
.custom-checkbox input:checked ~ .checkmark:after { display: block; }

.btn-bulk-delete { background: rgba(255, 59, 48, 0.1); color: var(--danger); border: 1px solid rgba(255, 59, 48, 0.3); padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 6px; }
.btn-bulk-delete:hover { background: var(--danger); color: #fff; border-color: var(--danger);}
.btn-cancel-select { background: transparent; color: var(--dim); border: none; font-size: 13px; font-weight: 600; padding: 10px 14px; cursor: pointer; transition: 0.2s; }
.btn-cancel-select:hover { color: #fff; }

/* 
======================================================
7. KARTU PENGALAMAN (GRID 2 KOLOM YANG BARU)
======================================================
*/
/* Ini kuncinya biar jadi 2 baris ke samping */
.exp-grid-container { 
    display: grid; 
    grid-template-columns: repeat(2, 1fr); 
    gap: 24px; 
}

@media (max-width: 900px) { 
    .exp-grid-container { grid-template-columns: 1fr; } 
}

/* Desain Kartu High-End (Glassmorphism ringan) */
.exp-card-item { 
    background: rgba(255, 255, 255, 0.015); 
    border: 1px solid rgba(255, 255, 255, 0.05); 
    border-radius: 16px; 
    padding: 24px; 
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
    display: flex; 
    flex-direction: column;
}

.exp-card-item:hover { 
    border-color: var(--cyan); 
    background: rgba(78, 155, 224, 0.03); 
    transform: translateY(-5px); 
    box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
}

.exp-card-item.row-selected { 
    border-color: var(--cyan); 
    background: rgba(78, 155, 224, 0.08); 
}

.exp-card-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 20px; 
    border-bottom: 1px dashed var(--line); 
    padding-bottom: 16px; 
}

.exp-number { 
    font-family: 'JetBrains Mono', monospace; 
    font-size: 11px; 
    font-weight: 700; 
    color: var(--cyan); 
    letter-spacing: 1px; 
}

.btn-remove-skill-sm { 
    background: transparent; 
    color: var(--dim); 
    border: 1px solid transparent; 
    width: 32px; 
    height: 32px; 
    border-radius: 8px; 
    cursor: pointer; 
    transition: 0.2s; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 16px;
}
.btn-remove-skill-sm:hover { 
    background: var(--danger); 
    color: #fff; 
}

/* 

*/
.grid-2-inner { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 16px; 
}

.card-input-group { 
    margin-bottom: 16px; 
    display: flex; 
    flex-direction: column; 
}

.card-input-group label { 
    font-size: 10.5px; 
    color: var(--dim); 
    margin-bottom: 8px; 
    font-weight: 700; 
    text-transform: uppercase; 
    letter-spacing: 1px;
}

.card-input-group input, 
.card-input-group textarea {
    width: 100%;
    background: rgba(10, 14, 23, 0.6); 
    border: 1px solid rgba(255,255,255,0.06);
    color: #fff;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    transition: 0.3s;
    font-family: 'Inter', sans-serif;
    box-sizing: border-box;
}

.card-input-group input:focus, 
.card-input-group textarea:focus {
    border-color: var(--cyan);
    background: rgba(10, 14, 23, 0.9);
    box-shadow: 0 0 0 3px rgba(78, 155, 224, 0.1);
    outline: none;
}

.card-input-group textarea { 
    resize: vertical; 
}

/* Tombol Tambah Card Baru (Garis Putus-putus) */
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
    margin-top: 24px;
}

.btn-add-dashed:hover { 
    border-color: var(--cyan); 
    color: var(--cyan); 
    background: rgba(78, 155, 224, 0.05); 
}

/* 

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

.slide-dots { display: flex; align-items: center; gap: 8px; }
.dot { width: 9px; height: 9px; border-radius: 50%; background: var(--line); cursor: pointer; transition: 0.3s; }
.dot:hover { background: var(--dim); }
.dot.active { background: var(--cyan); width: 24px; border-radius: 6px; }

.empty-state-box { text-align: center; padding: 40px; background: rgba(255,255,255,0.02); border: 1px dashed var(--line); border-radius: 16px; color: var(--dim); margin-bottom: 20px; }
.empty-state-box i { font-size: 36px; margin-bottom: 12px; opacity: 0.5; }
.empty-state-box p { margin: 0; font-size: 14px; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
</style>

<style>
/* 

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

.warning-icon-wrap { background: rgba(255, 255, 255, 0.2); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px;}
.warning-text { display: flex; flex-direction: column; color: #fff; font-family: 'Inter', sans-serif;}
.warning-text strong { font-size: 14px; font-weight: 700; font-family: 'Sora', sans-serif;}
.warning-text span { font-size: 12.5px; opacity: 0.9; margin-top: 2px;}

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translate(-50%, -50px); }
.slide-down-enter-to, .slide-down-leave-from { opacity: 1; transform: translate(-50%, 0); }

/* TOAST & MODAL GLOBAL */
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