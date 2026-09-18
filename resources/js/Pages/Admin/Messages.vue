<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    messages: { type: Array, default: () => [] }
});

const page = usePage();

// ==========================================
// STATE
// ==========================================
const searchQuery = ref('');
const activeFilter = ref('all'); // all | unread | read
const sortOrder = ref('desc'); // desc = terbaru dulu, asc = terlama dulu
const selectedIds = ref([]);
const viewingMessage = ref(null);
const isModalOpen = ref(false);

// Modal konfirmasi custom (pengganti confirm() bawaan browser)
const confirmState = ref({ show: false, message: '', onConfirm: null });

const askConfirm = (message, onConfirm) => {
    confirmState.value = { show: true, message, onConfirm };
};

const closeConfirm = () => { confirmState.value.show = false; };

const confirmYes = () => {
    const action = confirmState.value.onConfirm;
    confirmState.value.show = false;
    if (action) action();
};

// ==========================================
// FORMAT TANGGAL
// ==========================================
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// ==========================================
// FILTER, PENCARIAN & SORTING
// ==========================================
const unreadCount = computed(() => props.messages.filter(m => !m.is_read).length);
const readCount = computed(() => props.messages.length - unreadCount.value);

const filteredMessages = computed(() => {
    let list = props.messages;

    if (activeFilter.value === 'unread') list = list.filter(m => !m.is_read);
    else if (activeFilter.value === 'read') list = list.filter(m => m.is_read);

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(m => {
            const formattedDate = formatDate(m.created_at).toLowerCase();
            return (m.name && m.name.toLowerCase().includes(q)) ||
                   (m.email && m.email.toLowerCase().includes(q)) ||
                   (m.message && m.message.toLowerCase().includes(q)) ||
                   formattedDate.includes(q);
        });
    }

    return [...list].sort((a, b) => {
        // Belum dibaca selalu di atas, apa pun urutan tanggalnya
        if (!!a.is_read !== !!b.is_read) return a.is_read ? 1 : -1;

        const da = new Date(a.created_at).getTime();
        const db = new Date(b.created_at).getTime();
        return sortOrder.value === 'asc' ? da - db : db - da;
    });
});

const toggleSort = () => {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
};

const setFilter = (filter) => {
    activeFilter.value = filter;
};

// ==========================================
// PAGINATION (mengikuti gaya referensi: bernomor + ellipsis)
// ==========================================
const currentPage = ref(1);
const itemsPerPage = ref(10);

watch([searchQuery, itemsPerPage, activeFilter], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredMessages.value.length / itemsPerPage.value)));

const paginatedMessages = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredMessages.value.slice(start, start + itemsPerPage.value);
});

// Menghasilkan array seperti [1, 2, 3, '...', 8, 9, 10]
const paginationRange = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const delta = 1;
    const core = new Set([1, total]);

    for (let i = current - delta; i <= current + delta; i++) {
        if (i > 1 && i < total) core.add(i);
    }

    const sorted = [...core].sort((a, b) => a - b);
    const withDots = [];
    let last = null;

    sorted.forEach(i => {
        if (last !== null) {
            if (i - last === 2) withDots.push(last + 1);
            else if (i - last > 2) withDots.push('...');
        }
        withDots.push(i);
        last = i;
    });

    return withDots;
});

const goToPage = (p) => { if (typeof p === 'number') currentPage.value = p; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };

// ==========================================
// SELEKSI & BULK ACTION
// ==========================================
const selectAll = computed({
    get: () => paginatedMessages.value.length > 0 && paginatedMessages.value.every(m => selectedIds.value.includes(m.id)),
    set: (val) => {
        const idsOnPage = paginatedMessages.value.map(m => m.id);
        if (val) {
            selectedIds.value = [...new Set([...selectedIds.value, ...idsOnPage])];
        } else {
            selectedIds.value = selectedIds.value.filter(id => !idsOnPage.includes(id));
        }
    }
});

const clearSelection = () => { selectedIds.value = []; };

// Tombol "Tandai Dibaca" hanya relevan jika ada pesan belum dibaca di antara yang dipilih
const selectedHasUnread = computed(() =>
    props.messages.some(m => selectedIds.value.includes(m.id) && !m.is_read)
);

const inertiaOpts = { preserveScroll: true, preserveState: true };

const applyBulkAction = (actionType) => {
    if (selectedIds.value.length === 0) return;

    if (actionType === 'delete') {
        askConfirm(`${selectedIds.value.length} pesan yang dipilih akan dihapus secara permanen dan tidak bisa dikembalikan.`, () => {
            router.post('/admin/messages/bulk-delete', { ids: selectedIds.value }, {
                ...inertiaOpts,
                onSuccess: () => {
                    selectedIds.value = [];
                    if (paginatedMessages.value.length === 0 && currentPage.value > 1) currentPage.value--;
                }
            });
        });
    } else if (actionType === 'read') {
        router.post('/admin/messages/bulk-read', { ids: selectedIds.value }, {
            ...inertiaOpts,
            onSuccess: () => selectedIds.value = []
        });
    }
};

// ==========================================
// AKSI PER PESAN
// ==========================================
const readMessage = (msg) => {
    viewingMessage.value = msg;
    isModalOpen.value = true;
    if (!msg.is_read) router.post(`/admin/messages/${msg.id}/read`, {}, inertiaOpts);
};

const deleteMessage = (id) => {
    askConfirm('Pesan ini akan dihapus secara permanen dan tidak bisa dikembalikan.', () => {
        router.delete(`/admin/messages/${id}`, {
            ...inertiaOpts,
            onSuccess: () => {
                selectedIds.value = selectedIds.value.filter(selectedId => selectedId !== id);
                if (paginatedMessages.value.length === 1 && currentPage.value > 1) currentPage.value--;
            }
        });
    });
};

const deleteFromModal = () => {
    if (!viewingMessage.value) return;
    const id = viewingMessage.value.id;
    askConfirm('Pesan ini akan dihapus secara permanen dan tidak bisa dikembalikan.', () => {
        router.delete(`/admin/messages/${id}`, {
            ...inertiaOpts,
            onSuccess: () => {
                selectedIds.value = selectedIds.value.filter(selectedId => selectedId !== id);
                closeModal();
            }
        });
    });
};

const markAllRead = () => router.post('/admin/messages/read-all', {}, inertiaOpts);

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => { viewingMessage.value = null; }, 300);
};

// ==========================================
// AVATAR
// ==========================================
const getInitial = (name) => name ? name.charAt(0).toUpperCase() : '?';

const avatarPalette = ['#2563EB', '#7C3AED', '#059669', '#D97706', '#DB2777', '#0891B2'];
const getAvatarColor = (name) => {
    if (!name) return avatarPalette[0];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return avatarPalette[Math.abs(hash) % avatarPalette.length];
};

</script>

<template>
    <Head title="Kotak Masuk Pesan" />

    <div class="saas-wrapper">
        <main class="saas-main">

            <header class="page-header">
                <div class="header-content">
                    <Link href="/admin" class="btn-back"><i class='bx bx-arrow-back'></i> Kembali</Link>
                    <h1>Kotak Masuk Pesan</h1>
                </div>
                
            </header>

            <div v-if="$page.props.flash?.success_msg" class="alert-success">
                <i class='bx bx-check-circle'></i> {{ $page.props.flash.success_msg }}
            </div>
            <div v-if="$page.props.errors?.message" class="alert-error">
                <i class='bx bx-error-circle'></i> {{ $page.props.errors.message }}
            </div>

            <!-- Kartu Tabel Utama bergaya SaaS -->
            <div class="saas-card">

                <!-- Toolbar Atas: Filter Tab + Pencarian -->
                <div class="saas-toolbar">
                    <div class="filter-tabs">
                        <button class="filter-tab" :class="{ active: activeFilter === 'all' }" @click="setFilter('all')">
                            Semua <span class="tab-count">{{ messages.length }}</span>
                        </button>
                        <button class="filter-tab" :class="{ active: activeFilter === 'unread' }" @click="setFilter('unread')">
                            Belum Dibaca <span class="tab-count">{{ unreadCount }}</span>
                        </button>
                        <button class="filter-tab" :class="{ active: activeFilter === 'read' }" @click="setFilter('read')">
                            Sudah Dibaca <span class="tab-count">{{ readCount }}</span>
                        </button>
                    </div>

                    <div class="saas-search">
                        <i class='bx bx-search'></i>
                        <input type="text" v-model="searchQuery" placeholder="Cari nama, email, atau isi pesan...">
                    </div>
                </div>

                <!-- Tabel Data -->
                <div class="table-responsive">
                    <table class="saas-table">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" class="saas-checkbox" v-model="selectAll"></th>
                                <th width="260">Pengirim</th>
                                <th>Pesan</th>
                                <th width="200" class="sortable-th" @click="toggleSort">
                                    Tanggal Diterima
                                    <i :class="sortOrder === 'asc' ? 'bx bx-sort-up' : 'bx bx-sort-down'"></i>
                                </th>
                                <th width="70" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="paginatedMessages.length === 0">
                                <td colspan="5" class="empty-state">
                                    <i class='bx bx-envelope-open empty-icon'></i>
                                    <p v-if="searchQuery">Tidak ada pesan yang cocok dengan pencarian "{{ searchQuery }}".</p>
                                    <p v-else-if="activeFilter === 'unread'">Semua pesan sudah dibaca.</p>
                                    <p v-else-if="activeFilter === 'read'">Belum ada pesan yang dibaca.</p>
                                    <p v-else>Kotak masuk masih kosong.</p>
                                </td>
                            </tr>
                            <tr v-for="msg in paginatedMessages" :key="msg.id" :class="{ 'row-unread': !msg.is_read }">
                                <td><input type="checkbox" class="saas-checkbox" :value="msg.id" v-model="selectedIds"></td>
                                <td>
                                    <div class="user-cell">
                                        <span v-if="!msg.is_read" class="unread-dot" title="Belum dibaca"></span>
                                        <div class="avatar-circle" :style="{ background: getAvatarColor(msg.name) + '1A', color: getAvatarColor(msg.name) }">
                                            {{ getInitial(msg.name) }}
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name">{{ msg.name }}</div>
                                            <div class="user-email">{{ msg.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="msg-snippet" @click="readMessage(msg)">
                                        {{ msg.message }}
                                    </div>
                                </td>
                                <td class="date-col">{{ formatDate(msg.created_at) }}</td>
                                <td>
                                    <div class="action-icons">
                                        <button v-if="!msg.is_read" class="icon-btn" @click="readMessage(msg)" title="Baca">
                                            <i class='bx bx-show'></i>
                                        </button>
                                        <button class="icon-btn icon-del" @click="deleteMessage(msg.id)" title="Hapus">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Bar Seleksi (muncul saat ada yang dicentang) -->
                <transition name="fade-slide">
                    <div class="selection-bar" v-if="selectedIds.length > 0">
                        <div class="selection-info">
                            <i class='bx bx-check-square'></i>
                            {{ selectedIds.length }} pesan dipilih
                        </div>
                        <div class="selection-actions">
                            <button class="btn-sel-ghost" @click="clearSelection">Batal</button>
                            <button class="btn-sel-outline" v-if="selectedHasUnread" @click="applyBulkAction('read')">
                                <i class='bx bx-envelope-open'></i> Tandai Dibaca
                            </button>
                            <button class="btn-sel-danger" @click="applyBulkAction('delete')">
                                <i class='bx bx-trash'></i> Hapus Terpilih
                            </button>
                        </div>
                    </div>
                </transition>

                <!-- Footer Pagination bergaya SaaS -->
                <div class="saas-pagination">
                    <div class="per-page-selector">
                        <span>Item per halaman</span>
                        <select v-model="itemsPerPage" class="per-page-select">
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                        </select>
                    </div>

                    <div class="pagination-info" v-if="filteredMessages.length > 0">
                        Menampilkan {{ paginatedMessages.length }} dari {{ filteredMessages.length }} pesan
                    </div>

                    <div class="page-numbers" v-if="totalPages > 1">
                        <button class="page-arrow" @click="prevPage" :disabled="currentPage === 1"><i class='bx bx-chevron-left'></i></button>
                        <template v-for="(p, idx) in paginationRange" :key="idx">
                            <span v-if="p === '...'" class="page-dots">&hellip;</span>
                            <button v-else class="page-num" :class="{ active: currentPage === p }" @click="goToPage(p)">
                                {{ p }}
                            </button>
                        </template>
                        <button class="page-arrow" @click="nextPage" :disabled="currentPage === totalPages"><i class='bx bx-chevron-right'></i></button>
                    </div>
                </div>

            </div>
        </main>

        <!-- MODAL BACA PESAN -->
        <div class="modal-backdrop" :class="{ 'modal-show': isModalOpen }" @click="closeModal">
            <div class="modal-box modal-lg" @click.stop v-if="viewingMessage">
                <div class="modal-header">
                    <div class="modal-header-title">
                        <div class="avatar-circle avatar-lg" :style="{ background: getAvatarColor(viewingMessage.name) + '1A', color: getAvatarColor(viewingMessage.name) }">
                            {{ getInitial(viewingMessage.name) }}
                        </div>
                        <div>
                            <h3>{{ viewingMessage.name }}</h3>
                            <span class="modal-subtitle">{{ viewingMessage.email }}</span>
                        </div>
                    </div>
                    <button class="btn-close" @click="closeModal"><i class='bx bx-x'></i></button>
                </div>
                <div class="modal-body">
                    <div class="info-row">
                        <span class="info-label">Diterima</span>
                        <span class="info-value">{{ formatDate(viewingMessage.created_at) }}</span>
                    </div>
                    <div class="msg-box">
                        <div class="message-content">{{ viewingMessage.message }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-modal btn-modal-danger" @click="deleteFromModal">
                        <i class='bx bx-trash'></i> Hapus Pesan
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI (pengganti alert bawaan browser) -->
        <div class="confirm-backdrop" :class="{ 'modal-show': confirmState.show }" @click="closeConfirm">
            <div class="confirm-box" @click.stop v-if="confirmState.show">
                <div class="confirm-icon"><i class='bx bx-trash'></i></div>
                <h4 class="confirm-title">Hapus pesan?</h4>
                <p class="confirm-message">{{ confirmState.message }}</p>
                <div class="confirm-actions">
                    <button class="btn-confirm-cancel" @click="closeConfirm">Batal</button>
                    <button class="btn-confirm-danger" @click="confirmYes">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ========================================= */
/* TEMA SAAS (Terang, Bersih, Modern) */
/* ========================================= */
.saas-wrapper {
    --bg-page: #F5F7FA;
    --bg-surface: #FFFFFF;
    --text-dark: #1E293B;
    --text-muted: #64748B;
    --border-light: #E2E8F0;
    --primary-color: #2563EB;
    --primary-bg: #EFF6FF;
    --danger-color: #EF4444;
    --danger-bg: #FEF2F2;
    --success-color: #059669;
    font-family: 'Inter', sans-serif;
    background-color: var(--bg-page);
    min-height: 100vh;
    color: var(--text-dark);
}

@media (prefers-color-scheme: dark) {
    .saas-wrapper {
        --bg-page: #0F172A; --bg-surface: #1E293B;
        --text-dark: #F8FAFC; --text-muted: #94A3B8;
        --border-light: #334155; --primary-bg: rgba(37, 99, 235, 0.15);
        --danger-bg: rgba(239, 68, 68, 0.12);
    }
}

.saas-main { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }

.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; gap: 16px; flex-wrap: wrap; }
.btn-back { display: inline-flex; align-items: center; gap: 4px; color: var(--text-muted); font-size: 13px; font-weight: 500; text-decoration: none; margin-bottom: 8px; }
.btn-back:hover { color: var(--primary-color); }
.header-content h1 { font-family: 'Sora', sans-serif; font-size: 24px; font-weight: 700; margin: 0; }
.page-header .btn-saas-outline { margin-top: 4px; }

.alert-success, .alert-error { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; }
.alert-success { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
.alert-error { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }

/* KARTU TABEL */
.saas-card { background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }

/* TOOLBAR */
.saas-toolbar { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border-light); flex-wrap: wrap; gap: 16px; }

.btn-saas-outline { padding: 8px 16px; background: transparent; border: 1px solid var(--border-light); border-radius: 6px; color: var(--text-dark); font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.btn-saas-outline:hover { background: var(--bg-page); border-color: var(--text-muted); }

/* FILTER TABS */
.filter-tabs { display: flex; gap: 4px; background: var(--bg-page); padding: 4px; border-radius: 8px; }
.filter-tab { padding: 7px 14px; background: transparent; border: none; border-radius: 6px; color: var(--text-muted); font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.15s; white-space: nowrap; }
.filter-tab:hover { color: var(--text-dark); }
.filter-tab.active { background: var(--bg-surface); color: var(--primary-color); box-shadow: 0 1px 2px rgba(0,0,0,0.06); }
.tab-count { font-size: 11px; padding: 1px 6px; border-radius: 10px; background: var(--border-light); color: var(--text-muted); }
.filter-tab.active .tab-count { background: var(--primary-bg); color: var(--primary-color); }

.saas-search { position: relative; width: 100%; max-width: 280px; }
.saas-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 16px; }
.saas-search input { width: 100%; padding: 8px 12px 8px 36px; background: var(--bg-page); border: 1px solid transparent; border-radius: 6px; color: var(--text-dark); font-size: 13px; outline: none; transition: 0.2s; }
.saas-search input:focus { border-color: var(--primary-color); background: var(--bg-surface); box-shadow: 0 0 0 3px var(--primary-bg); }

/* TABEL */
.table-responsive { width: 100%; overflow-x: auto; }
.saas-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
.saas-table th, .saas-table td { padding: 14px 24px; border-bottom: 1px solid var(--border-light); }
.saas-table th { font-weight: 500; color: var(--text-muted); font-size: 12px; }
.saas-table tbody tr { transition: 0.15s; }
.saas-table tbody tr:hover { background: var(--bg-page); }

.sortable-th { cursor: pointer; user-select: none; display: table-cell; }
.sortable-th i { margin-left: 4px; font-size: 13px; vertical-align: -1px; }
.sortable-th:hover { color: var(--text-dark); }

.saas-checkbox {
    appearance: none;
    -webkit-appearance: none;
    width: 17px;
    height: 17px;
    border-radius: 5px;
    border: 1.5px solid var(--border-light);
    background: var(--bg-surface);
    cursor: pointer;
    position: relative;
    flex-shrink: 0;
    transition: 0.15s ease;
}
.saas-checkbox:hover { border-color: var(--primary-color); }
.saas-checkbox:checked { background: var(--primary-color); border-color: var(--primary-color); }
.saas-checkbox:checked::after {
    content: '';
    position: absolute;
    left: 5px;
    top: 2px;
    width: 4px;
    height: 8px;
    border: solid #FFF;
    border-width: 0 1.5px 1.5px 0;
    transform: rotate(45deg);
}

/* Identitas User & Avatar */
.user-cell { display: flex; align-items: center; gap: 12px; }
.avatar-circle { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; flex-shrink: 0; }
.avatar-circle.avatar-lg { width: 44px; height: 44px; font-size: 16px; }
.user-details { display: flex; flex-direction: column; min-width: 0; }
.user-name { font-weight: 600; color: var(--text-dark); }
.user-email { font-size: 12px; color: var(--text-muted); }

/* Status Belum Dibaca — cukup satu titik kecil, tidak perlu tanda berlapis */
.unread-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--primary-color); flex-shrink: 0; }

/* Snippet Pesan */
.msg-snippet { color: var(--text-muted); cursor: pointer; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; word-break: break-all; }
.date-col { font-size: 13px; color: var(--text-muted); white-space: nowrap; }

/* Ikon Aksi: Baca (hanya jika belum dibaca) & Hapus */
.action-icons { display: flex; justify-content: center; gap: 4px; }
.icon-btn { width: 32px; height: 32px; border-radius: 6px; background: transparent; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.icon-btn:hover { background: var(--bg-page); color: var(--primary-color); }
.icon-del:hover { color: var(--danger-color); }

.text-center { text-align: center; }

.empty-state { text-align: center; padding: 56px 24px !important; color: var(--text-muted); }
.empty-icon { font-size: 36px; color: var(--border-light); display: block; margin: 0 auto 8px; }

/* BAR SELEKSI */
.selection-bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: var(--primary-bg); border-bottom: 1px solid var(--border-light); flex-wrap: wrap; gap: 12px; }
.selection-info { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--primary-color); }
.selection-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.btn-sel-ghost, .btn-sel-outline, .btn-sel-danger { padding: 7px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; border: 1px solid transparent; transition: 0.15s; }
.btn-sel-ghost { background: transparent; color: var(--text-muted); }
.btn-sel-ghost:hover { color: var(--text-dark); }
.btn-sel-outline { background: var(--bg-surface); border-color: var(--border-light); color: var(--text-dark); }
.btn-sel-outline:hover { border-color: var(--primary-color); color: var(--primary-color); }
.btn-sel-danger { background: var(--danger-color); color: #FFF; }
.btn-sel-danger:hover { background: #B91C1C; }

.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.2s ease; }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-6px); }

/* PAGINATION SAAS */
.saas-pagination { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; flex-wrap: wrap; gap: 16px; }
.per-page-selector { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-muted); }
.per-page-select { padding: 4px 8px; border: 1px solid var(--border-light); border-radius: 4px; background: var(--bg-surface); color: var(--text-dark); font-size: 12px; outline: none; cursor: pointer; }

.pagination-info { font-size: 12px; color: var(--text-muted); }

.page-numbers { display: flex; align-items: center; gap: 4px; }
.page-arrow, .page-num { min-width: 30px; height: 30px; padding: 0 8px; background: transparent; border: 1px solid transparent; border-radius: 6px; color: var(--text-muted); font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.page-arrow:hover:not(:disabled), .page-num:hover { background: var(--bg-page); }
.page-num.active { background: var(--primary-color); color: #FFF; }
.page-arrow:disabled { opacity: 0.4; cursor: not-allowed; }
.page-dots { color: var(--text-muted); padding: 0 4px; font-size: 13px; }

/* MODAL BACA PESAN */
.modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: 0.3s; }
.modal-backdrop.modal-show { opacity: 1; pointer-events: auto; }
.modal-box { background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: 16px; width: 90%; max-width: 550px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); transform: scale(0.95); transition: 0.3s; display: flex; flex-direction: column; max-height: 85vh;}
.modal-backdrop.modal-show .modal-box { transform: scale(1); }

.modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.modal-header-title { display: flex; align-items: center; gap: 12px; }
.modal-header h3 { font-size: 16px; margin: 0; font-weight: 600; }
.modal-subtitle { font-size: 12px; color: var(--text-muted); }
.btn-close { background: transparent; border: none; font-size: 22px; color: var(--text-muted); cursor: pointer; flex-shrink: 0; }
.btn-close:hover { color: var(--danger-color); }

.modal-body { padding: 24px; overflow-y: auto; }
.info-row { display: flex; margin-bottom: 8px; font-size: 13px; }
.info-label { width: 80px; color: var(--text-muted); font-weight: 500; }
.info-value { color: var(--text-dark); font-weight: 500; }
.msg-box { margin-top: 16px; }

.message-content {
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: break-word;
    line-height: 1.6;
    color: var(--text-dark);
    background: var(--bg-page);
    padding: 16px;
    border-radius: 8px;
    font-size: 14px;
}

.modal-footer { padding: 16px 24px; border-top: 1px solid var(--border-light); display: flex; }
.btn-modal { padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; text-decoration: none; transition: 0.15s; }
.btn-modal-danger { background: var(--danger-bg); color: var(--danger-color); margin-left: auto; }
.btn-modal-danger:hover { background: var(--danger-color); color: #FFF; }

/* MODAL KONFIRMASI (pengganti alert/confirm bawaan browser) */
.confirm-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); z-index: 10000; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: 0.2s; }
.confirm-backdrop.modal-show { opacity: 1; pointer-events: auto; }
.confirm-box { background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: 14px; width: 90%; max-width: 340px; padding: 24px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.15); transform: scale(0.95); transition: 0.2s; }
.confirm-backdrop.modal-show .confirm-box { transform: scale(1); }
.confirm-icon { width: 44px; height: 44px; border-radius: 50%; background: var(--danger-bg); color: var(--danger-color); font-size: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
.confirm-title { font-size: 15px; font-weight: 600; color: var(--text-dark); margin: 0 0 6px; }
.confirm-message { font-size: 13px; color: var(--text-muted); line-height: 1.5; margin: 0 0 20px; }
.confirm-actions { display: flex; gap: 8px; }
.btn-confirm-cancel, .btn-confirm-danger { flex: 1; padding: 9px 0; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: 0.15s; }
.btn-confirm-cancel { background: var(--bg-page); border-color: var(--border-light); color: var(--text-dark); }
.btn-confirm-cancel:hover { border-color: var(--text-muted); }
.btn-confirm-danger { background: var(--danger-color); color: #FFF; }
.btn-confirm-danger:hover { background: #B91C1C; }

@media (max-width: 600px) {
    .saas-toolbar { flex-direction: column; align-items: stretch; }
    .filter-tabs { overflow-x: auto; }
    .saas-search { max-width: 100%; }
    .saas-pagination { flex-direction: column; align-items: stretch; text-align: center; }
    .selection-bar { flex-direction: column; align-items: stretch; }
    .selection-actions { justify-content: stretch; }
    .selection-actions button { flex: 1; justify-content: center; }
    .modal-footer { flex-direction: column; }
    .btn-modal-danger { margin-left: 0; }
}
</style>