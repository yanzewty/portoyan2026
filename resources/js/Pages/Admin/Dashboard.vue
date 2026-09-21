<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

// Menerima data props dari backend Laravel (profil dan statistik data)
defineProps({
    profile: { type: Object, default: () => ({}) },
    totalMessages: { type: Number, default: 0 },
    totalKeahlian: { type: Number, default: 0 },
    totalProjects: { type: Number, default: 0 }
});

// State untuk mengatur status sidebar (buka/tutup) dan animasi muat halaman
const isSidebarCollapsed = ref(false);
const isLoaded = ref(false);

// Menjalankan animasi transisi masuk saat komponen selesai dimuat
onMounted(() => {
    setTimeout(() => { isLoaded.value = true; }, 100);
});
</script>

<template>
    <Head title="Beranda Dashboard" />

    <div class="app-wrapper" :class="{ 'sidebar-collapsed': isSidebarCollapsed }">
        
        <!-- SIDEBAR NAVIGASI -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand">
                    <div class="brand-icon"><i class='bx bx-cube-alt'></i></div>
                    <div class="brand-text">
                        <span class="brand-name">Porto Admin</span>
                        <span class="brand-sub">Bar</span>
                    </div>
                </div>
            </div>

            <!-- Tombol toggle untuk menciutkan/memperluas sidebar -->
            <button class="collapse-btn" @click="isSidebarCollapsed = !isSidebarCollapsed">
                <i class='bx' :class="isSidebarCollapsed ? 'bx-menu' : 'bx-arrow-to-left'"></i>
                <span class="collapse-text">Menu</span>
            </button>


                <!-- menu untuk icon nya  -->
            <div class="menu-label">MENU UTAMA</div>
            <nav class="sidebar-nav">
                <Link href="/admin" class="nav-item active" style="--i:1">
                    <i class='bx bx-grid-alt'></i> 
                    <span class="nav-text">Dashboard</span>
                    <div class="tooltip">Dashboard</div>
                </Link>
                <Link href="/admin/home" class="nav-item" style="--i:2">
                    <i class='bx bx-home-smile'></i> 
                    <span class="nav-text">Edit Home</span>
                    <div class="tooltip">Edit Home</div>
                </Link>
                <Link href="/admin/about" class="nav-item" style="--i:3">
                    <i class='bx bx-user'></i> 
                    <span class="nav-text">Tentang Saya</span>
                    <div class="tooltip">Tentang Saya</div>
                </Link>
                <Link href="/admin/latar-belakang-skill" class="nav-item" style="--i:4">
                    <i class='bx bx-layer'></i> 
                    <span class="nav-text">Latar Skill</span>
                    <div class="tooltip">Latar Skill</div>
                </Link>
                <Link href="/admin/bidang-keahlian" class="nav-item" style="--i:5">
                    <i class='bx bx-wrench'></i> 
                    <span class="nav-text">Bidang Keahlian</span>
                    <div class="tooltip">Bidang Keahlian</div>
                </Link>
                <Link href="/admin/organizations" class="nav-item" style="--i:6">
                    <i class='bx bx-group'></i> 
                    <span class="nav-text">Organisasi</span>
                    <div class="tooltip">Organisasi</div>
                </Link>
               
                
                <Link href="/admin/messages" class="nav-item" style="--i:8">
                    <i class='bx bx-envelope'></i> 
                    <span class="nav-text">Pesan Masuk</span>
                    <span v-if="totalMessages > 0" class="badge-count">{{ totalMessages }}</span>
                    <div class="tooltip">Pesan Masuk</div>
                </Link>
            </nav>

            <!-- Tombol Logout -->
            <div class="sidebar-bottom">
                <Link href="/logout" method="post" as="button" class="nav-item logout-btn">
                    <i class='bx bx-log-out'></i> 
                    <span class="nav-text">Logout</span>
                    <div class="tooltip">Logout</div>
                </Link>
            </div>
        </aside>

        <!-- KONTEN UTAMA -->
        <main class="main-content">
            <button class="mobile-toggle" @click="isSidebarCollapsed = !isSidebarCollapsed">
                <i class='bx bx-menu-alt-left'></i>
            </button>

            <!-- Header Halaman & Indikator Pesan -->
            <header class="dash-header" :class="{'stagger-in': isLoaded}" style="--delay: 1">
                <div class="header-text">
                    <h1>Selamat Datang, Admin Akamsi</h1>
                    <p>NO BEAT NO SWEET.</p>
                </div>
                
                <Link v-if="totalMessages > 0" href="/admin/messages" class="alert-pill pulse-anim">
                    <div class="alert-icon"><i class='bx bx-bell bx-tada'></i></div>
                    <div class="alert-info">
                        <strong>{{ totalMessages }} Pesan Baru</strong>
                        <span>Cek inbox sekarang</span>
                    </div>
                    <i class='bx bx-chevron-right arrow-go'></i>
                </Link>
            </header>

            <!-- Kotak Statistik Atas -->
            <div class="top-stats-grid">
                <div class="hero-purple-card" :class="{'stagger-in': isLoaded}" style="--delay: 2">
                    <div class="hero-top">
                        <span class="hero-label">Portofolio</span>
                        <div class="hero-icon-circle"><i class='bx bx-globe'></i></div>
                    </div>
                    <div class="hero-mid">
                        <h2>Hallo Admin</h2>
                    </div>
                    <div class="hero-bottom">
                        <a href="/" target="_blank" class="btn-glass">
                            Lihat Website <i class='bx bx-right-arrow-alt'></i>
                        </a>
                    </div>
                    <div class="hero-glow"></div>
                </div>

                <Link href="/admin/messages" class="stat-clean-card" :class="{'stagger-in': isLoaded}" style="--delay: 3">
                    <div class="stat-header">
                        <div class="icon-wrap purple-light"><i class='bx bx-envelope'></i></div>
                        <span class="stat-title">Pesan Baru</span>
                    </div>
                    <div class="stat-body">
                        <h3>{{ totalMessages }}</h3>
                        <div class="stat-trend trend-up">Belum dibaca</div>
                    </div>
                </Link>
            </div>

            <!-- Bagian Akses Cepat Menu Admin -->
            <div class="content-section" :class="{'stagger-in': isLoaded}" style="--delay: 4">
                <div class="section-title">
                    <h4>Akses Cepat Pengelolaan</h4>
                </div>
                
                <div class="quick-access-grid">
                    <Link href="/admin/home" class="qa-card hover-anim" :class="{'stagger-in': isLoaded}" style="--delay: 5">
                        <i class='bx bx-home-smile qa-icon'></i>
                        <div class="qa-text">Edit Home & Profil</div>
                    </Link>
                    <Link href="/admin/about" class="qa-card hover-anim" :class="{'stagger-in': isLoaded}" style="--delay: 6">
                        <i class='bx bx-user qa-icon'></i>
                        <div class="qa-text">Tentang Saya</div>
                    </Link>
                    <Link href="/admin/latar-belakang-skill" class="qa-card hover-anim" :class="{'stagger-in': isLoaded}" style="--delay: 7">
                        <i class='bx bx-layer qa-icon'></i>
                        <div class="qa-text">Latar Belakang Skill</div>
                    </Link>
                    <Link href="/admin/bidang-keahlian" class="qa-card hover-anim" :class="{'stagger-in': isLoaded}" style="--delay: 8">
                        <i class='bx bx-wrench qa-icon'></i>
                        <div class="qa-text">Bidang Keahlian</div>
                    </Link>
                    <Link href="/admin/organizations" class="qa-card hover-anim" :class="{'stagger-in': isLoaded}" style="--delay: 9">
                        <i class='bx bx-group qa-icon'></i>
                        <div class="qa-text">Jejak Organisasi</div>
                    </Link>
                    <Link href="/admin/messages" class="qa-card hover-anim" :class="{'stagger-in': isLoaded}" style="--delay: 10">
                        <i class='bx bx-envelope qa-icon'></i>
                        <div class="qa-text">Pesan</div>
                    </Link>
                </div>
            </div>
            
        </main>

    </div>
</template>

<style scoped>

.app-wrapper {
    --bg-app: #F8FAFC;
    --bg-sidebar: #FFFFFF;
    --bg-card: #FFFFFF;
    --text-main: #0F172A;
    --text-muted: #64748B;
    --border-soft: #F1F5F9;
    --primary: #7C3AED;
    --primary-light: #F5F3FF;
    --shadow-soft: 0 10px 40px -10px rgba(0, 0, 0, 0.05);
    display: flex; 
    height: 100vh; 
    width: 100vw;
    overflow: hidden; 
    background-color: var(--bg-app);
    color: var(--text-main); 
    font-family: 'Inter', sans-serif;
}

@media (prefers-color-scheme: dark) {
    .app-wrapper {
        --bg-app: #0B0F19; --bg-sidebar: #111827; --bg-card: #151E2F;
        --text-main: #F8FAFC; --text-muted: #94A3B8; --border-soft: #1F2937;
        --primary: #8B5CF6; --primary-light: rgba(139, 92, 246, 0.15);
        --shadow-soft: 0 10px 40px -10px rgba(0, 0, 0, 0.4);
    }
}

.stagger-in { opacity: 0; transform: translateY(15px); animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; animation-delay: calc(var(--delay) * 0.05s); }
@keyframes slideUpFade { to { opacity: 1; transform: translateY(0); } }

.sidebar { width: 250px; background-color: var(--bg-sidebar); border-right: 1px solid var(--border-soft); display: flex; flex-direction: column; height: 100vh; transition: width 0.4s cubic-bezier(0.25, 1, 0.5, 1); z-index: 100; overflow: hidden; }
.sidebar-header { padding: 25px 20px 10px 20px; flex-shrink: 0; }
.brand { display: flex; align-items: center; gap: 12px; white-space: nowrap; overflow: hidden;}
.brand-icon { min-width: 36px; height: 36px; background: var(--primary); color: #fff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.brand-text { display: flex; flex-direction: column; transition: opacity 0.3s; }
.brand-name { font-weight: 800; font-size: 15px; color: var(--text-main); font-family: 'Sora', sans-serif; }
.brand-sub { font-size: 10px; color: var(--text-muted); font-weight: 600; letter-spacing: 0.5px;}

.collapse-btn { margin: 0 20px 15px 20px; padding: 10px; display: flex; align-items: center; gap: 12px; background: transparent; border: 1px solid var(--border-soft); border-radius: 10px; color: var(--text-muted); font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; white-space: nowrap; overflow: hidden; flex-shrink: 0; }
.collapse-btn:hover { background: var(--border-soft); color: var(--text-main); }
.collapse-btn i { font-size: 18px; min-width: 18px; text-align: center;}
.menu-label { font-size: 10px; font-weight: 700; color: var(--text-muted); letter-spacing: 1px; margin-bottom: 10px; padding-left: 25px; white-space: nowrap; transition: opacity 0.3s; flex-shrink: 0; }

.sidebar-nav { padding: 0 15px; display: flex; flex-direction: column; gap: 4px; flex: 1; overflow-y: auto; overflow-x: hidden; scrollbar-width: none; -ms-overflow-style: none; }
.sidebar-nav::-webkit-scrollbar { display: none; } 
.nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; position: relative; border-radius: 10px; color: var(--text-muted); text-decoration: none; font-weight: 500; font-size: 13px; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1); border: none; background: transparent; cursor: pointer; width: 100%; white-space: nowrap; animation: slideRight 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateX(-10px); animation-delay: calc(var(--i) * 0.04s); }
@keyframes slideRight { to { opacity: 1; transform: translateX(0); } }
.nav-item i { font-size: 18px; min-width: 18px; text-align: center; transition: 0.3s;}
.nav-item:hover { background-color: var(--bg-app); color: var(--text-main); transform: translateX(5px); }
.nav-item.active { background-color: var(--primary-light); color: var(--primary); font-weight: 700; }
.nav-item.active i { transform: scale(1.15); }
.badge-count { margin-left: auto; background: var(--primary); color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 20px; font-weight: 700; }
.sidebar-bottom { padding: 15px; margin-top: auto; border-top: 1px solid var(--border-soft); flex-shrink: 0; }
.logout-btn:hover { background: rgba(239, 68, 68, 0.1); color: #EF4444; }

.tooltip { position: absolute; left: 100%; margin-left: 15px; background: var(--text-main); color: var(--bg-app); padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; opacity: 0; pointer-events: none; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); white-space: nowrap; z-index: 1000; box-shadow: 0 5px 15px rgba(0,0,0,0.2); transform: translateX(-10px); }
.tooltip::before { content: ''; position: absolute; top: 50%; right: 100%; transform: translateY(-50%); border-width: 4px; border-style: solid; border-color: transparent var(--text-main) transparent transparent; }
.app-wrapper.sidebar-collapsed .nav-item:hover .tooltip { opacity: 1; transform: translateX(0); }
.app-wrapper.sidebar-collapsed .sidebar { width: 80px; }
.app-wrapper.sidebar-collapsed .brand-text, .app-wrapper.sidebar-collapsed .collapse-text, .app-wrapper.sidebar-collapsed .menu-label, .app-wrapper.sidebar-collapsed .nav-text, .app-wrapper.sidebar-collapsed .badge-count { opacity: 0; pointer-events: none; width: 0; display: none; }
.app-wrapper.sidebar-collapsed .collapse-btn { justify-content: center; padding: 10px 0; }
.app-wrapper.sidebar-collapsed .nav-item { justify-content: center; padding: 12px 0; }
.app-wrapper.sidebar-collapsed .nav-item i { margin: 0; font-size: 20px; }
.app-wrapper.sidebar-collapsed .brand { justify-content: center; margin-left: -5px; }
.app-wrapper.sidebar-collapsed .nav-item:hover { transform: translateY(-3px); }

.main-content { flex: 1; display: flex; flex-direction: column; padding: 25px 35px; height: 100vh; box-sizing: border-box; transition: 0.5s cubic-bezier(0.25, 1, 0.5, 1); }
.mobile-toggle { display: none; }
.dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-shrink: 0; }
.header-text h1 { font-family: 'Sora', sans-serif; font-size: 24px; font-weight: 800; margin: 0 0 4px 0; letter-spacing: -0.5px;}
.header-text p { color: var(--text-muted); font-size: 13px; margin: 0; }

.alert-pill { display: flex; align-items: center; gap: 12px; padding: 8px 14px 8px 8px; background: var(--bg-card); border: 1px solid var(--primary); border-radius: 50px; text-decoration: none; box-shadow: var(--shadow-soft); transition: 0.3s; }
.alert-pill:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(124, 58, 237, 0.2); }
.pulse-anim { animation: pulseGlow 2s infinite; }
@keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(124, 58, 237, 0); } 100% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0); } }
.alert-icon { width: 30px; height: 30px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.alert-info { display: flex; flex-direction: column; }
.alert-info strong { color: var(--text-main); font-size: 12px; font-family: 'Sora', sans-serif; }
.alert-info span { color: var(--text-muted); font-size: 10px; }
.arrow-go { font-size: 18px; color: var(--primary); margin-left: 5px; transition: 0.3s; }
.alert-pill:hover .arrow-go { transform: translateX(3px); }

.top-stats-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; margin-bottom: 25px; flex-shrink: 0;}
.hero-purple-card { background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%); border-radius: 20px; padding: 25px; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; box-shadow: 0 10px 25px rgba(109, 40, 217, 0.25); height: 180px; }
.hero-purple-card:hover .hero-glow { transform: scale(1.1) rotate(10deg); }
.hero-top { display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2;}
.hero-label { color: rgba(255,255,255,0.8); font-size: 13px; font-weight: 500; }
.hero-icon-circle { width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 18px; backdrop-filter: blur(5px);}
.hero-mid h2 { color: #fff; font-size: 30px; font-weight: 800; font-family: 'Sora', sans-serif; margin: 8px 0 4px 0; position: relative; z-index: 2; letter-spacing: -0.5px;}
.hero-bottom { margin-top: auto; position: relative; z-index: 2;}
.btn-glass { background: rgba(255,255,255,0.95); color: #6D28D9; padding: 8px 18px; border-radius: 50px; text-decoration: none; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; transition: 0.3s; }
.btn-glass:hover { transform: translateY(-2px) scale(1.02); box-shadow: 0 8px 15px rgba(0,0,0,0.15); }
.hero-glow { position: absolute; right: -50px; bottom: -50px; width: 200px; height: 200px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%); pointer-events: none; z-index: 1; transition: all 1s ease;}

.stat-clean-card { background: var(--bg-card); border: 1px solid var(--border-soft); border-radius: 20px; padding: 25px; box-shadow: var(--shadow-soft); display: flex; flex-direction: column; text-decoration: none; transition: 0.3s; height: 180px; }
.stat-clean-card:hover { transform: translateY(-4px); border-color: var(--primary); box-shadow: 0 15px 30px -10px rgba(124, 58, 237, 0.15); }
.stat-header { display: flex; align-items: center; gap: 12px; margin-bottom: auto; }
.icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.purple-light { background: var(--primary-light); color: var(--primary); }
.stat-title { font-weight: 700; font-size: 13px; color: var(--text-main); font-family: 'Sora', sans-serif;}
.stat-body { margin-top: 15px; }
.stat-body h3 { font-size: 36px; font-weight: 800; color: var(--text-main); margin: 0 0 8px 0; font-family: 'Sora', sans-serif; line-height: 1;}
.stat-trend { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; display: inline-block; }
.trend-up { background: rgba(16, 185, 129, 0.1); color: #10B981; }

.content-section { display: flex; flex-direction: column; flex: 1; min-height: 0; }
.section-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-shrink: 0; }
.section-title h4 { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 800; color: var(--text-main); margin: 0; }
.quick-access-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; flex: 1; }
.qa-card { background: var(--bg-card); border: 1px solid var(--border-soft); border-radius: 18px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1); box-shadow: var(--shadow-soft); position: relative; overflow: hidden; z-index: 1; padding: 15px; }
.qa-card::before { content: ''; position: absolute; inset: 0; background: var(--primary); z-index: -1; transform: scaleY(0); transform-origin: bottom; transition: transform 0.3s cubic-bezier(0.25, 1, 0.5, 1); }
.hover-anim:hover { transform: translateY(-4px); border-color: var(--primary); box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.2); }
.hover-anim:hover::before { transform: scaleY(1); }
.qa-icon { font-size: 32px; color: var(--primary); margin-bottom: 8px; transition: 0.3s ease; display: inline-block;}
.qa-text { font-size: 13px; font-weight: 700; color: var(--text-main); transition: 0.3s ease; font-family: 'Sora-sans', sans-serif;}
.hover-anim:hover .qa-icon { color: #fff; transform: scale(1.1) rotate(5deg); }
.hover-anim:hover .qa-text { color: #fff; }

@media (max-width: 900px) {
    .app-wrapper { overflow: auto; height: auto; }
    .main-content { height: auto; overflow: visible; }
    .top-stats-grid { grid-template-columns: 1fr; }
    .quick-access-grid { grid-template-columns: repeat(2, 1fr); min-height: 400px; }
}
</style>