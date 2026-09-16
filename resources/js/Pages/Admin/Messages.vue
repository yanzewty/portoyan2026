<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    messages: { type: Array, default: () => [] }
});

// ==========================================
// SETUP TOAST NOTIFICATION DINAMIS
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
// FORMAT TANGGAL
// ==========================================
const formatDate = (dateString) => {
    if (!dateString) return '';
    const options = { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

// ==========================================
// FITUR SEARCH & HIGHLIGHT (TERMASUK TANGGAL)
// ==========================================
const searchQuery = ref('');

const filteredMessages = computed(() => {
    if (!searchQuery.value) return props.messages;
    const q = searchQuery.value.toLowerCase();
    
    return props.messages.filter(m => {
        const dateStr = formatDate(m.created_at).toLowerCase();
        return (m.name && m.name.toLowerCase().includes(q)) ||
               (m.email && m.email.toLowerCase().includes(q)) ||
               (m.message && m.message.toLowerCase().includes(q)) ||
               dateStr.includes(q); // Cek apakah pencarian cocok dengan tanggal
    });
});

const unreadCount = computed(() => props.messages.filter(m => !m.is_read).length);

const escapeHTML = (str) => {
    if (!str) return '';
    return str.replace(/[&<>'"]/g, tag => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    }[tag] || tag));
};

const highlightText = (text) => {
    let safeText = escapeHTML(text);
    if (!searchQuery.value) return safeText;
    const safeQuery = escapeHTML(searchQuery.value);
    const regex = new RegExp(`(${safeQuery})`, 'gi');
    return safeText.replace(regex, '<span class="highlight-text">$1</span>');
};

// ==========================================
// FITUR PAGINATION (HALAMAN)
// ==========================================
const currentPage = ref(1);
const itemsPerPage = ref(5); 

const totalPages = computed(() => {
    return Math.ceil(filteredMessages.value.length / itemsPerPage.value) || 1;
});

const paginatedMessages = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredMessages.value.slice(start, end);
});

watch(searchQuery, () => {
    currentPage.value = 1; // Kembali ke halaman 1 saat mencari
});

// ==========================================
// MODAL KONFIRMASI UMUM
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
// MODAL BACA PESAN
// ==========================================
const readModal = ref({ isOpen: false, data: null });

const openReadModal = (msg) => {
    readModal.value = { isOpen: true, data: msg };
    if (!msg.is_read) {
        router.post(`/admin/messages/${msg.id}/read`, {}, { preserveScroll: true });
    }
};
const closeReadModal = () => { readModal.value.isOpen = false; readModal.value.data = null; };

// ==========================================
// LOGIKA TANDAI SEMUA DIBACA
// ==========================================
const markAllAsRead = () => {
    openModal(
        'Tandai Semua Dibaca', 
        'Yakin ingin menandai semua pesan baru sebagai telah dibaca?', 
        'bx-envelope-open', 
        '#34C77B', 
        'Ya, Tandai Semua', 
        () => { 
            router.post('/admin/messages/mark-all-read', {}, {
                preserveScroll: true,
                onSuccess: () => displayToast('Semua pesan ditandai telah dibaca.', 'success')
            });
        }, true
    );
};

// ==========================================
// LOGIKA MODE PILIH (HAPUS & BACA MASAL)
// ==========================================
const isSelectMode = ref(false); 
const selectedIds = ref([]); 

const toggleSelectMode = () => { isSelectMode.value = true; };
const cancelSelectMode = () => { isSelectMode.value = false; selectedIds.value = []; };

const toggleSelection = (id) => {
    const pos = selectedIds.value.indexOf(id);
    if (pos === -1) selectedIds.value.push(id);
    else selectedIds.value.splice(pos, 1);
};

const isAllSelected = computed(() => paginatedMessages.value.length > 0 && paginatedMessages.value.every(msg => selectedIds.value.includes(msg.id)));

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        paginatedMessages.value.forEach(msg => {
            const pos = selectedIds.value.indexOf(msg.id);
            if (pos !== -1) selectedIds.value.splice(pos, 1);
        });
    } else {
        paginatedMessages.value.forEach(msg => {
            if (!selectedIds.value.includes(msg.id)) selectedIds.value.push(msg.id);
        });
    }
};

const deleteSelectedMessages = () => {
    openModal('Hapus Terpilih', `Yakin menghapus ${selectedIds.value.length} pesan yang dicentang secara permanen?`, 'bx-trash-alt', '#FF3B30', 'Ya, Hapus', 
        () => { 
            router.post('/admin/messages/bulk-delete', { ids: selectedIds.value }, {
                preserveScroll: true,
                onSuccess: () => {
                    cancelSelectMode();
                    displayToast('Pesan terpilih berhasil dihapus.', 'success');
                    if (paginatedMessages.value.length === 0 && currentPage.value > 1) {
                        currentPage.value--;
                    }
                }
            });
        }, true);
};

const readSelectedMessages = () => {
    openModal('Baca Terpilih', `Tandai ${selectedIds.value.length} pesan yang dicentang sebagai telah dibaca?`, 'bx-envelope-open', '#34C77B', 'Ya, Tandai Dibaca', 
        () => { 
            router.post('/admin/messages/bulk-read', { ids: selectedIds.value }, {
                preserveScroll: true,
                onSuccess: () => {
                    cancelSelectMode();
                    displayToast('Pesan terpilih ditandai telah dibaca.', 'success');
                }
            });
        }, true);
};

// ==========================================
// FUNGSI AKSI HAPUS TUNGGAL API
// ==========================================
const deleteMessage = (id) => {
    openModal('Hapus Pesan', 'Yakin ingin menghapus pesan ini secara permanen?', 'bx-trash', '#FF3B30', 'Ya, Hapus Permanen', 
        () => { 
            router.delete(`/admin/messages/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    displayToast('Pesan berhasil dihapus!', 'success');
                    if (paginatedMessages.value.length === 1 && currentPage.value > 1) {
                        currentPage.value--; 
                    }
                }
            });
        }, true);
};
</script>

<template>
    <Head title="Inbox Pesan - Admin" />

    <div class="admin-container">
        <div class="wrap-form">
            <div class="header-flex">
                <div>
                    <h1 class="main-title">Pesan Masuk</h1>
                    <p class="sub-title">Baca pesan dari pengunjung web portofoliomu.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <Link href="/admin" class="btn-outline"><i class='bx bx-arrow-back'></i> Kembali</Link>
                </div>
            </div>

            <div v-if="$page.props.flash?.success_msg" class="alert-succ">
                <i class='bx bx-check-shield' style="font-size: 20px;"></i> 
                <span>{{ $page.props.flash.success_msg }}</span>
            </div>

            <div class="glass-card">
                <div class="top-bar-flex">
                    <div class="form-title"><i class='bx bx-envelope' style="color:#4E9BE0"></i> Inbox Pengunjung</div>
                    <div class="search-box">
                        <i class='bx bx-search'></i>
                        <input type="text" v-model="searchQuery" placeholder="Cari nama, email, tanggal, isi..." />
                        <i v-if="searchQuery" class='bx bx-x clear-btn' @click="searchQuery = ''"></i>
                    </div>
                </div>
                
                <div class="select-mode-header">
                    <div class="skill-count">TOTAL: {{ filteredMessages.length }} PESAN DITEMUKAN</div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button v-if="!isSelectMode && unreadCount > 0 && !searchQuery" type="button" class="btn-mark-read" @click="markAllAsRead">
                            <i class='bx bx-check-double'></i> Baca Semua
                        </button>
                        
                        <button v-if="!isSelectMode && filteredMessages.length > 0" type="button" class="btn-toggle-select" @click="toggleSelectMode">
                            <i class='bx bx-check-square'></i> Mode Pilih
                        </button>
                    </div>
                </div>

                <div v-if="isSelectMode && filteredMessages.length > 0" class="bulk-action-bar">
                    <div class="left-bulk">
                        <label class="custom-checkbox">
                            <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll">
                            <span class="checkmark"></span>
                            <span style="user-select: none;">Pilih Semua di Halaman ({{ selectedIds.length }})</span>
                        </label>
                    </div>
                    <div class="right-bulk">
                        <button type="button" class="btn-cancel-select" @click="cancelSelectMode">Batal</button>
                        <button v-if="selectedIds.length > 0" type="button" class="btn-bulk-read" @click="readSelectedMessages">
                            <i class='bx bx-envelope-open'></i> Baca Terpilih
                        </button>
                        <button v-if="selectedIds.length > 0" type="button" class="btn-bulk-delete" @click="deleteSelectedMessages">
                            <i class='bx bx-trash'></i> Hapus Terpilih
                        </button>
                    </div>
                </div>

                <div v-if="filteredMessages.length === 0" class="empty-state">
                    <i class='bx bx-ghost'></i>
                    <span v-if="searchQuery">Tidak ada pesan yang cocok dengan "{{ searchQuery }}".</span>
                    <span v-else>Belum ada pesan masuk dari pengunjung.</span>
                </div>

                <!-- DAFTAR PESAN -->
                <div class="messages-container">
                    <div v-for="msg in paginatedMessages" :key="msg.id" class="msg-row" :class="{'row-selected': selectedIds.includes(msg.id), 'unread': !msg.is_read}">
                        
                        <div v-if="isSelectMode" class="checkbox-wrap" @click.stop>
                            <label class="custom-checkbox">
                                <input type="checkbox" :checked="selectedIds.includes(msg.id)" @change="toggleSelection(msg.id)">
                                <span class="checkmark"></span>
                            </label>
                        </div>

                        <div class="msg-content clickable-area" @click="!isSelectMode ? openReadModal(msg) : toggleSelection(msg.id)">
                            <div class="msg-top">
                                <div>
                                    <span v-if="!msg.is_read" class="badge-new">Baru</span>
                                    <h3 v-html="highlightText(msg.name)"></h3>
                                    <a :href="'mailto:' + msg.email" class="email-link" @click.stop v-html="highlightText(msg.email)"></a>
                                </div>
                                <!-- TANGGAL BISA DI HIGHLIGHT -->
                                <span class="msg-time" v-html="highlightText(formatDate(msg.created_at))"></span>
                            </div>
                            <div class="msg-snippet" v-html="highlightText(msg.message)"></div>
                        </div>

                        <div v-if="!isSelectMode" class="action-wrap">
                            <button @click.stop="deleteMessage(msg.id)" class="btn-delete-small" title="Hapus Pesan">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- NAVIGASI PAGINATION -->
                <div v-if="totalPages > 1" class="pagination-bar">
                    <button class="btn-page" :disabled="currentPage === 1" @click="currentPage--">
                        <i class='bx bx-chevron-left'></i> Prev
                    </button>
                    <span class="page-info">Halaman <b>{{ currentPage }}</b> dari {{ totalPages }}</span>
                    <button class="btn-page" :disabled="currentPage === totalPages" @click="currentPage++">
                        Next <i class='bx bx-chevron-right'></i>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL BACA PESAN -->
    <Teleport to="body">
        <div v-if="readModal.isOpen" class="modal-overlay" @click="closeReadModal"></div>
        <div v-if="readModal.isOpen" class="read-modal">
            <div class="read-modal-header">
                <h3><i class='bx bx-envelope-open'></i> Detail Pesan</h3>
                <button @click="closeReadModal" class="btn-close-modal"><i class='bx bx-x'></i></button>
            </div>
            <div class="read-modal-body">
                <div class="sender-info">
                    <div class="avatar">{{ readModal.data.name.charAt(0).toUpperCase() }}</div>
                    <div>
                        <h4>{{ readModal.data.name }}</h4>
                        <a :href="'mailto:' + readModal.data.email">{{ readModal.data.email }}</a>
                    </div>
                </div>
                <!-- TANGGAL DI MODAL JUGA BISA DI HIGHLIGHT -->
                <div class="msg-date" v-html="highlightText(formatDate(readModal.data.created_at))"></div>
                
                <div class="msg-full-text">
                    {{ readModal.data.message }}
                </div>
            </div>
            <div class="read-modal-footer">
                <button @click="closeReadModal" class="btn-primary-modal" style="width: 100%; justify-content: center;">
                    Tutup Pesan
                </button>
            </div>
        </div>
    </Teleport>

    
    <Teleport to="body">
        <div :class="['toast-notification', toastType, { 'show': showToast }]">
            <i :class="toastType === 'success' ? 'bx bx-check-circle' : 'bx bx-error-circle'" class="toast-icon"></i>
            <span>{{ toastMessage }}</span>
        </div>
    </Teleport>

    <Teleport to="body">
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
    background-color: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; min-height: 100vh; width: 100%; padding: 40px; box-sizing: border-box; position: relative; overflow-x: hidden;
}

.wrap-form { max-width: 900px; margin: 0 auto; animation: fadeIn 0.5s ease; padding-bottom: 60px;}
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.header-flex { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; margin-bottom: 30px;}
.main-title { font-family: 'Sora', sans-serif; font-size: 32px; font-weight: 800; margin: 0 0 6px 0; color: #fff; }
.sub-title { color: var(--dim); font-size: 14px; margin: 0; }

.btn-outline { padding: 12px 20px; border: 1px solid var(--line); border-radius: 12px; font-size: 13px; color: #EAEEF5; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; background: rgba(10,14,23,0.5); font-weight: 600; cursor: pointer; }
.btn-outline:hover { border-color: var(--cyan); color: #fff; background: rgba(78, 155, 224, 0.1); transform: translateY(-2px); }

.alert-succ { background: rgba(52,199,123,0.1); border-left: 4px solid #34C77B; color: #34C77B; padding: 16px 20px; border-radius: 12px; font-size: 14px; display: flex; align-items: center; gap: 10px; margin-bottom: 30px; font-weight: 600; }
.glass-card { background: rgba(16, 21, 31, 0.6); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); margin-bottom: 40px; padding: 35px; }
.form-title { font-size: 18px; color: #fff; display: flex; align-items: center; gap: 10px; font-weight: 700; font-family: 'Sora', sans-serif; margin: 0;}

.top-bar-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;}
.search-box { position: relative; width: 100%; max-width: 350px; }
.search-box i.bx-search { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--dim); font-size: 18px; }
.search-box input { width: 100%; background: rgba(10, 14, 23, 0.6); border: 1px solid var(--line); color: #EAEEF5; padding: 12px 40px; border-radius: 12px; font-size: 13.5px; transition: 0.3s; box-sizing: border-box; }
.search-box input:focus { border-color: var(--cyan); outline: none; box-shadow: 0 0 0 4px rgba(78, 155, 224, 0.1); }
.search-box .clear-btn { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--dim); cursor: pointer; font-size: 18px; transition: 0.2s;}
.search-box .clear-btn:hover { color: #fff; }

:deep(.highlight-text) { background-color: #FFD60A; color: #000; padding: 0 3px; border-radius: 3px; font-weight: bold; }

.select-mode-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px dashed rgba(255,255,255,0.05); }
.skill-count { font-size: 11px; font-family: 'JetBrains Mono', monospace; color: var(--dim); font-weight: 600; letter-spacing: 1px; text-transform: uppercase;}

.btn-toggle-select { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); color: var(--dim); padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.3s; }
.btn-toggle-select:hover { background: rgba(78, 155, 224, 0.1); border-color: var(--cyan); color: var(--cyan); }

.btn-mark-read { background: rgba(52, 199, 123, 0.1); border: 1px solid rgba(52, 199, 123, 0.3); color: #34C77B; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.3s; }
.btn-mark-read:hover { background: #34C77B; color: #fff; }

.bulk-action-bar { display: flex; justify-content: space-between; align-items: center; background: rgba(78, 155, 224, 0.08); padding: 12px 18px; border-radius: 12px; border: 1px dashed var(--cyan); margin-bottom: 20px; animation: fadeIn 0.3s ease; flex-wrap: wrap; gap: 10px;}
.left-bulk, .right-bulk { display: flex; align-items: center; gap: 12px; }

.custom-checkbox { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: var(--dim); font-weight: 600; }
.custom-checkbox input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
.checkmark { height: 22px; width: 22px; background-color: var(--bg); border: 2px solid var(--line); border-radius: 6px; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
.custom-checkbox:hover input ~ .checkmark { border-color: var(--cyan); }
.custom-checkbox input:checked ~ .checkmark { background-color: var(--cyan); border-color: var(--cyan); }
.checkmark:after { content: ""; display: none; width: 5px; height: 10px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); margin-bottom: 2px; }
.custom-checkbox input:checked ~ .checkmark:after { display: block; }
.checkbox-wrap { align-self: center; margin-right: 15px; }

.btn-bulk-read { background: rgba(52, 199, 123, 0.1); color: #34C77B; border: 1px solid rgba(52, 199, 123, 0.3); padding: 8px 16px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 6px; }
.btn-bulk-read:hover { background: #34C77B; color: #fff; }
.btn-bulk-delete { background: rgba(255, 59, 48, 0.1); color: #FF3B30; border: 1px solid rgba(255, 59, 48, 0.3); padding: 8px 16px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 6px; }
.btn-bulk-delete:hover { background: #FF3B30; color: #fff; }
.btn-cancel-select { background: transparent; color: var(--dim); border: none; font-size: 12.5px; font-weight: 600; padding: 8px 12px; cursor: pointer; transition: 0.2s; }
.btn-cancel-select:hover { color: #fff; }

.empty-state { background: rgba(135, 146, 166, 0.05); color: var(--dim); margin-bottom: 15px; font-size: 13.5px; text-align: center; padding: 40px; border: 1px dashed rgba(255, 255, 255, 0.1); border-radius: 14px; }
.empty-state i { font-size: 32px; margin-bottom: 10px; display: block; }

.messages-container { margin-bottom: 20px; }

.msg-row { display: flex; flex-wrap: nowrap; background: var(--panel-2); padding: 22px 25px; border-radius: 16px; border: 1px solid var(--line); margin-bottom: 12px; transition: 0.3s; align-items: stretch; position: relative; overflow: hidden;}
.msg-row:hover { border-color: rgba(78, 155, 224, 0.5); box-shadow: 0 4px 20px rgba(0,0,0,0.2); transform: translateX(4px); }
.msg-row.row-selected { border-color: var(--cyan); background: rgba(78, 155, 224, 0.05); transform: translateX(0); }
.msg-row.unread { border-left: 4px solid var(--cyan); background: rgba(255,255,255, 0.03); }

.clickable-area { cursor: pointer; flex: 1; display: flex; flex-direction: column; justify-content: center; width: 100%;}
.msg-top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 8px; }
.msg-top h3 { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 700; margin: 0; color: #fff; display: inline-block; margin-right: 10px;}
.email-link { font-family: 'Inter', sans-serif; font-size: 12.5px; color: var(--cyan) !important; text-decoration: none; transition: 0.2s; }
.email-link:hover { opacity: 0.8; }
.msg-time { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; color: var(--dim); background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 6px;}
.badge-new { background: var(--cyan); color: #000; font-size: 9px; font-weight: 800; padding: 3px 8px; border-radius: 10px; text-transform: uppercase; margin-right: 8px; vertical-align: middle; }
.msg-snippet { font-size: 13.5px; color: var(--text); line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; }

.action-wrap { align-self: center; margin-left: 15px; }
.btn-delete-small { background: rgba(255, 59, 48, 0.05); color: #FF3B30; border: 1px dashed rgba(255, 59, 48, 0.3); padding: 12px; border-radius: 10px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; height: 100%; font-size: 18px;}
.btn-delete-small:hover { background: #FF3B30; color: #fff; border-color: #FF3B30; border-style: solid; }

/* STYLE UNTUK PAGINATION */
.pagination-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 20px; border-top: 1px dashed rgba(255,255,255,0.05); }
.page-info { font-size: 13px; color: var(--dim); font-family: 'JetBrains Mono', monospace; }
.page-info b { color: var(--text); font-weight: 700; }
.btn-page { background: var(--panel-2); border: 1px solid var(--line); color: var(--text); padding: 10px 18px; border-radius: 10px; font-size: 13px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 5px; font-weight: 600; }
.btn-page i { font-size: 18px; }
.btn-page:not(:disabled):hover { border-color: var(--cyan); color: var(--cyan); background: rgba(78, 155, 224, 0.1); }
.btn-page:disabled { opacity: 0.4; cursor: not-allowed; }

@media (max-width: 768px) { 
    .top-bar-flex { flex-direction: column; align-items: flex-start; }
    .search-box { max-width: 100%; }
    .msg-row { padding: 18px; }
    .msg-top { flex-direction: column; align-items: flex-start; gap: 5px; }
}
</style>

<style>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
@import url('https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css');

:root {
    --bg: #0A0E17; 
    --panel: #10151F; 
    --panel-2: #141B29; 
    --line: #232D3E; 
    --dim: #8792A6; 
    --cyan: #4E9BE0;
    --text: #EAEEF5;
}

.read-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--panel); border: 1px solid var(--line); border-radius: 20px; width: 90%; max-width: 550px; z-index: 999999; box-shadow: 0 25px 50px rgba(0,0,0,0.5); animation: modalPop 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); display: flex; flex-direction: column; max-height: 90vh; color: var(--text); font-family: 'Inter', sans-serif;}
.read-modal-header { padding: 20px 25px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; }
.read-modal-header h3 { font-family: 'Sora', sans-serif; font-size: 18px; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px; }
.btn-close-modal { background: none; border: none; color: var(--dim); font-size: 24px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; }
.btn-close-modal:hover { color: #FF3B30; }

.read-modal-body { padding: 30px 25px; overflow-y: auto; }
.sender-info { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
.avatar { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, var(--cyan), #3763E0); color: #fff; font-size: 20px; font-weight: 700; display: flex; align-items: center; justify-content: center; font-family: 'Sora', sans-serif;}
.sender-info h4 { margin: 0 0 5px 0; color: #fff; font-size: 16px; font-family: 'Sora', sans-serif; }
.sender-info a { color: var(--cyan) !important; font-size: 13.5px; text-decoration: none; font-family: 'Inter', sans-serif;}
.msg-date { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--dim); margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed rgba(255,255,255,0.1); }
.msg-full-text { font-size: 14.5px; color: var(--text); line-height: 1.8; white-space: pre-wrap; font-family: 'Inter', sans-serif;}

.read-modal-footer { padding: 20px 25px; border-top: 1px solid var(--line); display: flex; justify-content: center; background: rgba(0,0,0,0.2); border-radius: 0 0 20px 20px; }
.btn-primary-modal { padding: 12px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; color: #fff; background: var(--cyan); border: none; transition: 0.2s; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 15px rgba(78, 155, 224, 0.3); font-family: 'Inter', sans-serif;}
.btn-primary-modal:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(78, 155, 224, 0.4); filter: brightness(1.1); }

.toast-notification { position: fixed; top: 30px; right: 30px; transform: translateX(150%); padding: 16px 24px; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); backdrop-filter: blur(10px); display: flex; align-items: center; gap: 12px; font-family: 'Inter', sans-serif; font-size: 13.5px; font-weight: 600; z-index: 999999; transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); max-width: 380px; }
.toast-notification.show { transform: translateX(0); }
.toast-notification.error { background: rgba(255, 59, 48, 0.15); border-left: 4px solid #FF3B30; color: #ffb3ad; }
.toast-notification.success { background: rgba(52, 199, 123, 0.15); border-left: 4px solid #34C77B; color: #34C77B; }
.modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(10, 14, 23, 0.85); backdrop-filter: blur(6px); z-index: 999998; }
.custom-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--panel); border: 1px solid var(--line); border-radius: 20px; padding: 35px 30px; width: 90%; max-width: 420px; text-align: center; z-index: 999999; box-shadow: 0 25px 50px rgba(0,0,0,0.5); animation: modalPop 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); font-family: 'Inter', sans-serif; color: var(--text);}
@keyframes modalPop { 0% { opacity: 0; transform: translate(-50%, -40%) scale(0.85); } 100% { opacity: 1; transform: translate(-50%, -50%) scale(1); } }
.modal-icon { width: 65px; height: 65px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
.modal-title { color: #fff; font-family: 'Sora', sans-serif; font-size: 20px; font-weight: 700; margin-bottom: 12px; }
.modal-desc { color: var(--dim); font-size: 14px; line-height: 1.6; margin-bottom: 30px; }
.modal-actions { display: flex; gap: 12px; justify-content: center; }
.btn-modal-cancel { padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; background: rgba(255,255,255,0.05); color: #EAEEF5; border: 1px solid rgba(255,255,255,0.1); transition: 0.2s; font-size: 14px; font-family: 'Inter', sans-serif;}
.btn-modal-cancel:hover { background: rgba(255,255,255,0.1); }
.btn-modal-confirm { padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; color: #fff; border: none; transition: 0.2s; box-shadow: 0 8px 15px rgba(0,0,0,0.2); font-size: 14px; font-family: 'Inter', sans-serif;}
.btn-modal-confirm:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(0,0,0,0.3); filter: brightness(1.1); }
</style>