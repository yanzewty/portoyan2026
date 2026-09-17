<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { onMounted, ref, computed, onUnmounted } from 'vue';

const props = defineProps({
    profile: { type: Object, default: () => ({}) },
    about: { type: Object, default: () => ({}) },
    panels: { type: Array, default: () => [] },
    dataKeahlian: { type: [Array, Object, String], default: () => [] },
    dataKeahlianSingkat: { type: Array, default: () => [] },
    skillHeader: { type: Object, default: () => ({}) },
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const typewriterText = ref('');
const showAllSkills = ref(false);
const ORG_VISIBLE_COUNT = 3;
const showAllOrgs = ref(false);

const parsedSkills = computed(() => {
    const skills = props.profile?.skills;
    if (!skills) return [{name:'HTML'}, {name:'CSS'}, {name:'LARAVEL'}];
    if (Array.isArray(skills)) return skills;
    try {
        const parsed = JSON.parse(skills);
        return Array.isArray(parsed) && parsed.length ? parsed : [{name:'HTML'}, {name:'CSS'}, {name:'LARAVEL'}];
    } catch {
        return [{name:'HTML'}, {name:'CSS'}, {name:'LARAVEL'}];
    }
});

const parsedEducation = computed(() => {
    const ed = props.profile?.education;
    const def = { tag: '04 / PENGALAMAN ORGANISASI', title: 'Jejak Kepemimpinan', desc: 'Peran yang membentuk cara saya bekerja dalam tim dan mengambil keputusan.' };
    if (!ed) return def;
    if (typeof ed === 'object') return ed;
    try { return JSON.parse(ed) || def; }
    catch { return def; }
});

const parsedExperiences = computed(() => {
    const exp = props.profile?.experiences;
    let result = [];
    
    if (Array.isArray(exp)) {
        result = exp;
    } else if (typeof exp === 'string') {
        try { result = JSON.parse(exp) || []; } catch { result = []; }
    }
    
    // Fallback data jika database belum mengirimkan data organisasi
    if (result.length === 0) {
        return [
            { posisi: 'Sekretaris Umum', periode: '2025 - Sekarang', instansi: 'OSIS SMK Negeri 1 Surabaya', deskripsi: 'Bertanggung jawab penuh atas administrasi organisasi, tata kelola surat-menyurat resmi, serta melakukan koordinasi intensif antar divisi.' },
            { posisi: 'Ketua Karang Taruna', periode: '2023 - Sekarang', instansi: 'Warga Setempat', deskripsi: 'Memimpin tim pemuda dalam merancang dan mengeksekusi kegiatan sosial kemasyarakatan.' }
        ];
    }
    
    return result;
});

const visibleOrgs = computed(() => {
    return showAllOrgs.value ? parsedExperiences.value : parsedExperiences.value.slice(0, ORG_VISIBLE_COUNT);
});

const displayKeahlian = computed(() => {
    let rawData = props.dataKeahlian;
    if (typeof rawData === 'string') {
        try { rawData = JSON.parse(rawData); } catch (e) { rawData = []; }
    }
    if (rawData && typeof rawData === 'object' && rawData.data) {
        rawData = rawData.data;
    }
    if (Array.isArray(rawData) && rawData.length > 0) return rawData;
    
    return [
        { id: 1, modul: 'MODULE / 01', judul: 'Pemrograman Web', deskripsi: 'Membangun aplikasi website dinamis menggunakan Laravel dan Vue.js yang responsif dan optimal.', kategori: 'DEVELOPMENT' },
        { id: 2, modul: 'MODULE / 02', judul: 'UI/UX & Poster Digital', deskripsi: 'Merancang antarmuka pengguna yang modern, bersih, dan mudah digunakan untuk pengalaman interaktif.', kategori: 'DESIGN & UI' },
        { id: 3, modul: 'MODULE / 03', judul: 'Kegiatan OSIS', deskripsi: 'Mengelola struktur data menggunakan MySQL untuk kebutuhan aplikasi dengan relasi yang efisien.', kategori: 'LEADERSHIP' }
    ];
});

const getDeskripsi = (item, index) => {
    if (item && item.deskripsi && item.deskripsi.trim() !== '') return item.deskripsi;
    if (index === 0) return "Membangun sistem website dinamis dan responsif menggunakan PHP dan framework Laravel yang optimal dan cepat.";
    if (index === 1) return "Merancang antarmuka web dan aplikasi (UI) yang ramah pengguna, estetis, dan memiliki alur interaksi yang jelas.";
    return "Aktif mengasah kepemimpinan dan komunikasi sosial. Berpengalaman mengurus tata kelola administrasi dan organisasi secara efisien.";
};

const displayKeahlianSingkat = computed(() => {
    if (props.dataKeahlianSingkat && props.dataKeahlianSingkat.length > 0) return props.dataKeahlianSingkat;
    return [
        { name: 'HTML 5', icon: 'fab fa-html5', color: '#E34F26' },
        { name: 'CSS 3', icon: 'fab fa-css3-alt', color: '#1572B6' },
        { name: 'JavaScript', icon: 'fab fa-js', color: '#F7DF1E' },
        { name: 'Laravel', icon: 'fab fa-laravel', color: '#FF2D20' },
        { name: 'Vue JS', icon: 'fab fa-vuejs', color: '#4FC08D' },
        { name: 'PHP', icon: 'fab fa-php', color: '#777BB4' }
    ];
});

const isModalOpen = ref(false);
const modalData = ref({ title: '', category: '', desc: '', image: '' });

const openModal = (title, category, desc, image) => {
    modalData.value = { title, category, desc, image };
    isModalOpen.value = true;
    document.body.style.overflow = 'hidden';
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => { document.body.style.overflow = 'auto'; }, 300);
};

const contactForm = ref({ name: '', email: '', message: '', hp_website_url: '' });
const isSubmitting = ref(false);
const responseMsg = ref({ show: false, text: '', isError: false });
const showSpamModal = ref(false);

const closeSpamModal = () => {
    showSpamModal.value = false;
    document.body.style.overflow = 'auto';
    contactForm.value = { name: '', email: '', message: '', hp_website_url: '' };
};

const invalidListener = router.on('httpException', (event) => {
    event.preventDefault(); 
    if (event.detail.response?.status === 429) {
        showSpamModal.value = true;
        document.body.style.overflow = 'hidden';
    } else {
        responseMsg.value = { show: true, text: 'Terjadi kesalahan sistem.', isError: true };
    }
    isSubmitting.value = false;
});

const exceptionListener = router.on('networkError', (event) => {
    event.preventDefault(); 
    showSpamModal.value = true;
    document.body.style.overflow = 'hidden';
    isSubmitting.value = false;
});

onUnmounted(() => {
    invalidListener();
    exceptionListener();
});


const submitContact = () => {
    if (isSubmitting.value) return; 

    isSubmitting.value = true;
    responseMsg.value.show = false;

    router.post('/contact/send', contactForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            responseMsg.value = { show: true, text: 'Terima kasih! Pesanmu berhasil dikirim.', isError: false };
            contactForm.value = { name: '', email: '', message: '', hp_website_url: '' }; 
        },
        onError: (errors) => {
            responseMsg.value = { show: true, text: 'Gagal mengirim. Pastikan form diisi dengan benar.', isError: true };
        },
        onFinish: () => {
            isSubmitting.value = false;
            setTimeout(() => { responseMsg.value.show = false; }, 5800);
        }
    });
};

onMounted(() => {
    window.scrollTo(0, 0);
    if (window.location.hash) {
        history.replaceState(null, null, window.location.pathname);
    }

    const pre = document.getElementById('preloader');
    if(pre) setTimeout(()=>{ pre.classList.add('leave'); setTimeout(()=>pre.remove(), 300); }, 150);

    const textToType = props.profile?.role || 'IT ENGINEERING & IT Enthusiast';
    let typeIndex = 0;
    function typeWriter(){
        if(typeIndex < textToType.length){
            typewriterText.value += textToType.charAt(typeIndex);
            typeIndex++;
            setTimeout(typeWriter, 85);
        }
    }
    setTimeout(typeWriter, 900);

    const progressBar = document.querySelector('.progress');
    const docEl = document.documentElement;
    
    window.addEventListener('scroll', ()=>{
        let scrollable = docEl.scrollHeight - docEl.clientHeight;
        const pct = scrollable > 0 ? (docEl.scrollTop / scrollable) * 100 : 0;
        if(progressBar) progressBar.style.width = pct + '%';

        let currentSection = '';
        document.querySelectorAll('section[id]').forEach(section => {
            const sectionTop = section.offsetTop;
            if (window.scrollY >= sectionTop - 150) { 
                currentSection = section.getAttribute('id');
            }
        });

        if (currentSection) {
            if (window.scrollY < 100 || currentSection === 'home') {
                history.replaceState(null, null, window.location.pathname);
            } else if (window.location.hash !== `#${currentSection}`) {
                history.replaceState(null, null, `#${currentSection}`);
            }
        }
    }, { passive:true });

    document.querySelectorAll('[data-magnet]').forEach(btn=>{
        let rect = null;
        btn.addEventListener('mouseenter', ()=>{ rect = btn.getBoundingClientRect(); });
        btn.addEventListener('mousemove', e=>{
            if(!rect) rect = btn.getBoundingClientRect();
            let px = (e.clientX - rect.left - rect.width/2) * .25;
            let py = (e.clientY - rect.top - rect.height/2) * .35;
            requestAnimationFrame(()=>{ btn.style.transform = `translate(${px}px, ${py}px)`; });
        });
        btn.addEventListener('mouseleave', ()=>{ btn.style.transform='translate(0,0)'; rect = null; });
    });

    document.querySelectorAll('[data-tilt]').forEach(card=>{
        let rect = null;
        card.addEventListener('mouseenter', ()=>{ rect = card.getBoundingClientRect(); });
        card.addEventListener('mousemove', e=>{
            if(!rect) rect = card.getBoundingClientRect();
            let px = (e.clientX - rect.left) / rect.width;
            let py = (e.clientY - rect.top) / rect.height;
            requestAnimationFrame(()=>{
                card.style.transform = `rotateX(${(py-.5)*-8}deg) rotateY(${(px-.5)*8}deg) translateY(-4px)`;
                card.style.setProperty('--mx', (px*100)+'%');
                card.style.setProperty('--my', (py*100)+'%');
            });
        });
        card.addEventListener('mouseleave', ()=>{ card.style.transform='rotateX(0) rotateY(0)'; rect = null; });
    });

    const obs = new IntersectionObserver((entries)=>{
        entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('in'); else e.target.classList.remove('in'); });
    }, { threshold:.15 });
    document.querySelectorAll('.reveal, .card').forEach(el=>obs.observe(el));

    const slider = document.getElementById('skillsSlider');
    if(slider) {
        let isDown = false, startX, scrollLeft, isAutoScrolling = true;
        const autoScrollStep = () => {
            if(isAutoScrolling && !isDown){
                slider.scrollLeft += .6;
                if(slider.scrollLeft >= (slider.scrollWidth / 2)) slider.scrollLeft = 0;
            }
            requestAnimationFrame(autoScrollStep);
        };
        requestAnimationFrame(autoScrollStep);
        slider.addEventListener('mouseenter', ()=>{ isAutoScrolling = false; });
        slider.addEventListener('mouseleave', ()=>{ if(!isDown) isAutoScrolling = true; });
        slider.addEventListener('mousedown', e=>{ isDown = true; isAutoScrolling = false; startX = e.pageX - slider.offsetLeft; scrollLeft = slider.scrollLeft; });
        slider.addEventListener('mouseup', ()=>{ isDown = false; isAutoScrolling = true; });
        slider.addEventListener('mousemove', e=>{
            if(!isDown) return;
            e.preventDefault();
            slider.scrollLeft = scrollLeft - ((e.pageX - slider.offsetLeft) - startX) * 2;
        });
    }
});
</script>

<template>
  <Head :title="'Portfolio - ' + (profile?.name || 'Alfiansyah')" />

  <div class="preloader" id="preloader">
    <div class="preloader-logo">{{ (profile?.name || 'PORTFOLIO')?.toUpperCase() }}</div>
    <div class="preloader-bar"><span></span></div>
  </div>

  <div class="progress"></div>
  <div class="mesh"><div class="blob b1"></div><div class="blob b2"></div></div>
  <div class="noise"></div>

  <main>
    <nav>
      <div class="logo">{{ profile?.name || 'Portfolio' }}<span>.</span></div>
      <div class="navlinks">
        <a href="#home">Home</a>
        <a href="#About">About</a>
        <a href="#latar-belakang-skill">Latar Belakang & Skill</a>
        <a href="#keahlian-singkat">Keahlian Singkat</a>
        <a href="#organization">Organisasi</a>
        
        <div v-if="isAuthenticated" class="nav-admin-group">
          <Link href="/admin" class="nav-admin-btn edit-btn"><i class="fas fa-sliders-h"></i> Edit Portfolio</Link>
          <Link href="/logout" method="post" as="button" class="nav-admin-btn logout-btn" style="cursor:pointer;"><i class="fas fa-sign-out-alt"></i> Logout</Link>
        </div>
        <div v-else>
          <Link href="/login" class="nav-admin-btn login-btn"><i class="fas fa-lock"></i> Admin</Link>
        </div>
      </div>
      <a href="#contact" class="navcta">Hubungi Saya</a>
    </nav>

    <section class="hero" id="home">
      <div class="hero-copy">
        <div class="eyebrow" data-anim="fade"><span style="color:var(--cyan)">—</span> PORTOFOLIO SAYA &middot; IT ENGINEERING</div>
        <div class="split-title"><div class="row"><h1 class="title"><span class="word grad-text">{{ typewriterText }}</span></h1></div></div>
        <p class="lede" data-anim="fade">{{ profile?.about || 'Siswa kelas 12 IT Engineering dengan minat mendalam di bidang pengembangan web dan desain UI/UX.' }}</p>
        <div class="hero-cta" data-anim="fade"><a href="#contact" class="btn btn-primary" data-magnet>Hubungi Saya</a></div>

        <div class="marquee-wrap">
          <div class="marquee">
            <span v-for="n in 2" :key="n">
              <span v-for="(sk, i) in parsedSkills" :key="i">
                <span class="plus">+</span> &nbsp; {{ (sk.name || sk || '').toUpperCase() }} &nbsp;
              </span>

              <span class="plus">+</span> &nbsp;
            </span>

          </div>
        </div>
      </div>

      <div class="hero-img-wrap">
        <div class="hero-img">
          <div class="glow-ring"></div>
          <img :src="profile?.photo ? `/uploads/${profile.photo}` : '/uploads/1786586192_profil_IMG_20260707_112708_146.jpg'" alt="Foto Profil">
          <div class="cap">{{ profile?.address || 'Perumahan Palempertiwi, Menganti, Gresik' }}</div>
        </div>
        <div v-if="profile?.badge_1" class="float-badge fb1"><span class="fb-dot"></span> {{ profile.badge_1 }}</div>
        <div v-if="profile?.badge_2" class="float-badge fb2"><i class="fas fa-code" style="color:var(--cyan)"></i> {{ profile.badge_2 }}</div>
      </div>
    </section>

    <div style="display: flex; flex-direction: column; gap: 30px; width: 100%; margin: 80px 0;">
      <section id="About">
        <div class="about-panel reveal" style="height: auto; min-height: fit-content; padding-bottom: 40px; display: flex; flex-wrap: wrap; gap: 40px; width: 100%; margin: 0 auto;">
          <div style="flex: 1; min-width: 300px;">
            <div class="sec-tag" style="color:var(--cyan)">{{ about?.tag || '01 / TENTANG SAYA' }}</div>
            <h2 style="font-family: 'Sora', sans-serif; font-size: 38px; font-weight: 700; color: #fff; line-height: 1.3;">{{ about?.title || 'Membangun Solusi Digital dengan Logika & Kreativitas' }}</h2>
          </div>
          <div style="flex: 1.2; min-width: 300px; display: flex; flex-direction: column; gap: 20px;">
              <div style="padding: 30px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; background: rgba(255, 255, 255, 0.03); transition: 0.3s; word-break: break-word; overflow-wrap: break-word;" onmouseover="this.style.borderColor='var(--cyan)'; this.style.background='rgba(255, 255, 255, 0.06)';" onmouseout="this.style.borderColor='rgba(255, 255, 255, 0.1)'; this.style.background='rgba(255, 255, 255, 0.03)';">
                <div style="color: var(--dim); font-size: 15px; line-height: 1.8; white-space: pre-line;">{{ about?.description || 'Siswa kelas 12 IT Engineering...' }}</div>
              </div>
          </div>
        </div>
      </section>

      <section v-for="(panel, index) in panels" :key="index">
        <div class="about-panel reveal" style="height: auto; min-height: fit-content; padding-bottom: 40px; display: flex; flex-wrap: wrap; gap: 40px; width: 100%; margin: 0 auto;">
          <div style="flex: 1; min-width: 300px;">
            <div class="sec-tag" style="color:var(--cyan)">{{ panel.tag }}</div>
            <h2 style="font-family: 'Sora', sans-serif; font-size: 38px; font-weight: 700; color: #fff; line-height: 1.3;">{{ panel.title }}</h2>
          </div>
          <div style="flex: 1.2; min-width: 300px; display: flex; flex-direction: column; gap: 20px;" v-if="panel.description">
            <div style="padding: 30px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; background: rgba(255, 255, 255, 0.03); transition: 0.3s;" onmouseover="this.style.borderColor='var(--cyan)';" onmouseout="this.style.borderColor='rgba(255, 255, 255, 0.1)';">
                <div style="color: var(--dim); font-size: 15px; line-height: 1.8; white-space: pre-line;">{{ panel.description }}</div>
            </div>
          </div>
        </div>
      </section>
    </div> 

    <section id="latar-belakang-skill" style="margin-top: 60px;">
      <div class="sec-head reveal">
        <div><div class="sec-title" style="text-transform: uppercase;">{{ profile?.about_sub_2 || 'LATAR BELAKANG & SKILL' }}</div></div>
        <div class="sec-desc">{{ profile?.about_2 || 'Dokumentasi kegiatan pemrograman web, desain UI/UX, dan organisasi sosial.' }}</div>
      </div>
      
      <div class="cards" id="skillsGridContainer">
        <div v-for="(item, index) in displayKeahlian" :key="item.id || index" 
             class="card reveal" 
             v-show="index < 3 || showAllSkills"
             data-tilt 
             @click="openModal(item.judul, item.kategori, getDeskripsi(item, index), 
                item.gambar ? `/uploads/${item.gambar}` : 
                item.image ? `/uploads/${item.image}` : 
                item.foto ? `/uploads/${item.foto}` : 
                (index === 0 ? '/uploads/1786421475_g1_rpl.png' : 
                 index === 1 ? '/uploads/1786516895_g3_DSC07615.jpg' : 
                 '/uploads/1787207896_keahlian_IMG_20260715_071115_152.jpg')
             )">
          
          <div class="card-img">
            <img :src="item.gambar ? `/uploads/${item.gambar}` : 
                       item.image ? `/uploads/${item.image}` : 
                       item.foto ? `/uploads/${item.foto}` : 
                       item.file ? `/uploads/${item.file}` :
                       (index === 0 ? '/uploads/1786421475_g1_rpl.png' : 
                        index === 1 ? '/uploads/1786516895_g3_DSC07615.jpg' : 
                        '/uploads/1787207896_keahlian_IMG_20260715_071115_152.jpg')" 
                 :alt="item.judul">
          </div>
          
          <div class="card-body">
            <div class="idx">{{ item.modul }}</div>
            <h3>{{ item.judul }}</h3>
            <p>{{ getDeskripsi(item, index) }}</p>
            <div class="foot"><span>{{ item.kategori }}</span><span>Klik detail &rarr;</span></div>
          </div>
          
        </div>
      </div>
      
      <div v-if="displayKeahlian?.length > 3" style="text-align: center; width: 100%; position: relative; z-index: 5;">
        <button class="btn-toggle-skill" @click="showAllSkills = !showAllSkills">
          {{ showAllSkills ? 'Sembunyikan' : `Lihat Semua (${displayKeahlian.length})` }} 
          <i class="fas" :class="showAllSkills ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
      </div>
  </section>

    <section id="keahlian-singkat">
      <div class="sec-head reveal">
        <div>
          <div class="sec-tag" style="color:var(--cyan)">{{ skillHeader?.tag || '03 / KEAHLIAN SINGKAT' }}</div>
          <div class="sec-title">{{ skillHeader?.title || 'Keahlian Singkat Saya' }}</div>
        </div>
        <div class="sec-desc">{{ skillHeader?.description || 'Memadukan kemampuan teknis IT dengan tata kelola organisasi yang rapi.' }}</div>
      </div>
      <div id="skillsSlider" class="slider">
        <div class="slider-track">
          <template v-for="n in 2" :key="'loop'+n">
            <div v-for="(item, idx) in displayKeahlianSingkat" :key="idx" class="skill-chip">
              <div>
                <div class="ico" :style="`background: ${item.color}25; color: ${item.color}; box-shadow: 0 4px 15px ${item.color}30;`"><i :class="item.icon"></i></div>
                <h3>{{ item.name }}</h3>
              </div>
            </div>
          </template>
        </div>
      </div>
      <p class="hint"><i class="fas fa-arrows-alt-h"></i> geser ke kiri / kanan</p>
    </section>

    <section id="organization">
      <div class="sec-head reveal">
        <div><div class="sec-tag" style="color:var(--cyan)">{{ parsedEducation.tag }}</div><div class="sec-title">{{ parsedEducation.title }}</div></div>
        <div class="sec-desc">{{ parsedEducation.desc }}</div>
      </div>
      
      <div class="timeline-zigzag reveal">
        <div v-for="(exp, index) in visibleOrgs" :key="index" class="tz-item">
          <div class="tz-content" @click="openModal(exp?.posisi, exp?.periode, `${exp?.deskripsi}\n\nInstansi: ${exp?.instansi}`, null)">
            <div class="tz-role">{{ exp?.posisi }}</div>
            <div class="tz-org">{{ exp?.instansi }} &middot; {{ exp?.periode }}</div>
            <div class="tz-desc">{{ (exp?.deskripsi?.length > 120) ? exp.deskripsi.substring(0, 120) + '...' : exp.deskripsi }}</div>
          </div>
        </div>
      </div>

      <div v-if="parsedExperiences.length > ORG_VISIBLE_COUNT" style="text-align: center; width: 100%; position: relative; z-index: 5;">
        <button type="button" class="btn-toggle-org" @click="showAllOrgs = !showAllOrgs">
          {{ showAllOrgs ? 'Sembunyikan Sebagian' : `Lihat Semua (${parsedExperiences.length})` }}
          <i class="fas" :class="showAllOrgs ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
      </div>
    </section>

    <section id="contact">
      <div class="sec-head reveal" style="margin-bottom:34px">
        <div><div class="sec-tag" style="color:var(--cyan)">05 / CONTACT</div><div class="sec-title">Kirim Pesan</div></div>
        <div class="sec-desc">Punya pertanyaan, tawaran proyek, atau ingin berdiskusi? Kirim lewat form ini.</div>
      </div>

      <div class="contact-panel reveal">
        <div v-if="responseMsg.show" style="margin-bottom:22px;padding:14px 16px;border-radius:12px;font-size:13px;" :style="responseMsg.isError ? 'background:rgba(255,93,162,.1);border:1px solid rgba(255,93,162,.4);color:var(--cyan);' : 'background:rgba(78,225,214,.1);border:1px solid rgba(78,225,214,.4);color:var(--cyan);'">
            <i class="fas" :class="responseMsg.isError ? 'fa-exclamation-triangle' : 'fa-check-circle'"></i> {{ responseMsg.text }}
        </div>

        <form @submit.prevent="submitContact">
          <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;">
              <input type="text" v-model="contactForm.hp_website_url" tabindex="-1" autocomplete="off">
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
            <div class="field"><label>Nama Anda</label><input type="text" v-model="contactForm.name" required placeholder="Contoh: Budi"></div>
            <div class="field"><label>Email Anda</label><input type="email" v-model="contactForm.email" required placeholder="email@gmail.com"></div>
          </div>
          <div class="field" style="margin-bottom:24px;">
            <label>Pesan</label><textarea v-model="contactForm.message" rows="5" required placeholder="Tuliskan pesan..." style="resize:none;"></textarea>
          </div>
          <button type="submit" :disabled="isSubmitting" class="btn btn-primary" data-magnet style="width:100%;text-align:center;">
            <span v-if="!isSubmitting"><i class="fas fa-paper-plane"></i> Kirim Pesan Sekarang</span>
            <span v-else><i class="fas fa-spinner fa-spin"></i> Mengirim Pesan...</span>
          </button>
        </form>
      </div>
    </section>

    <footer class="reveal" id="footer">
      <div class="footer-title">Mari wujudkan ide<br>digital <span class="grad-text">berikutnya.</span></div>
      <a href="#contact" class="footer-cta" data-magnet>Hubungi Saya &rarr;</a>
      <div class="footer-links">
        <a :href="`mailto:${profile?.email || ''}`"><i class="fas fa-envelope"></i> {{ profile?.email || '' }}</a>
        <a :href="`https://wa.me/62${(profile?.phone || '').replace(/-/g, '').substring(1)}`"><i class="fab fa-whatsapp"></i> {{ profile?.phone || '' }}</a>
        <Link v-if="isAuthenticated" href="/admin" style="color:var(--cyan)">Panel Admin</Link>
        <Link v-else href="/login">Login Admin</Link>
      </div>
      <div class="footer-bottom">&copy; {{ new Date().getFullYear() }} {{ (profile?.name || 'PORTFOLIO')?.toUpperCase() }} &middot; {{ (profile?.address || '')?.toUpperCase() }}</div>
    </footer>
  </main>

  <!-- MODAL DETAIL KEAHLIAN / ORGANISASI -->
  <div class="modal-overlay" :class="{'modal-active': isModalOpen}">
    <div class="backdrop" @click="closeModal"></div>
    <div class="modal-box" :class="{'modal-scale': isModalOpen}">
      <button class="modal-close" @click="closeModal"><i class="fas fa-times"></i></button>
      <div v-if="modalData.image" class="modal-img"><img :src="modalData.image" alt="Detail"></div>
      <div class="modal-content">
        <div class="modal-cat">{{ modalData.category }}</div>
        <div class="modal-title">{{ modalData.title }}</div>
        <p class="modal-desc" style="white-space: pre-line;">{{ modalData.desc }}</p>
      </div>
    </div>
  </div>

  <!-- MODAL CUSTOM SPAM (ERROR 429) -->
  <div class="modal-overlay" :class="{'modal-active': showSpamModal}" style="z-index: 9999;">
    <!-- Ditambahkan fungsi closeSpamModal pada backdrop dan tombol -->
    <div class="backdrop" @click="closeSpamModal"></div>
    <div class="modal-box spam-modal" :class="{'modal-scale': showSpamModal}">
      <div class="spam-icon"><i class="fas fa-shield-alt"></i></div>
      <div class="spam-title">Tidak Terkirim</div>
      <p class="spam-desc">
        Terlalu banyak percobaan kirim pesan dalam waktu singkat. Silakan coba kirim pesan lagi di lain waktu.
      </p>
      <button @click="closeSpamModal" class="btn spam-btn">
        <i class="fas fa-check"></i> Kembali
      </button>
    </div>
  </div>
</template>

<style>
/* Bagian 1: Terstruktur ke bawah (Neat) dengan penjelasan */

/* Variabel warna dasar untuk keseluruhan UI */
:root {
    --bg: #0A0E17;
    --panel: #10151F;
    --panel-2: #141B29;
    --line: #232D3E;
    --text: #EAEEF5;
    --dim: #8792A6;
    --violet: #3763E0;
    --pink: #5C7A9E;
    --cyan: #4E9BE0;
    --gold: #C9A24A;
    --font-display: 'Sora', sans-serif;
    --font-body: 'Inter', sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
}

/* Reset margin, padding, dan perhitungan border untuk semua elemen */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Pengaturan dasar elemen HTML (efek scroll halus) */
html {
    scroll-behavior: smooth;
    scrollbar-width: thin;
    scrollbar-color: var(--line) var(--bg);
}

/* Pengaturan dasar elemen Body (latar belakang, teks, dan mencegah scroll horizontal) */
body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--font-body);
    overflow-x: hidden;
}

/* Warna saat teks di-highlight/diseleksi */
::selection {
    background: var(--pink);
    color: #0A0A12;
}

/* Menghilangkan garis bawah pada semua link/tautan */
a {
    color: inherit;
    text-decoration: none;
}

/* Kustomisasi lebar scrollbar browser (Webkit) */
::-webkit-scrollbar {
    width: 8px;
}

/* Warna latar belakang jalur scrollbar */
::-webkit-scrollbar-track {
    background: var(--bg);
}

/* Warna batang scrollbar yang bisa ditarik */
::-webkit-scrollbar-thumb {
    background: var(--line);
    border-radius: 10px;
}

/* Warna batang scrollbar saat kursor diarahkan ke atasnya */
::-webkit-scrollbar-thumb:hover {
    background: var(--pink);
}

/* Layar pemuatan (loading screen) awal */
.preloader {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: var(--bg);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 18px;
    transition: transform .9s cubic-bezier(.76,0,.24,1);
}

/* Efek transisi saat layar pemuatan menghilang ke atas */
.preloader.leave {
    transform: translateY(-100%);
}

/* Teks logo yang muncul di layar pemuatan */
.preloader-logo {
    font-family: var(--font-display);
    font-weight: 800;
    font-size: 14px;
    letter-spacing: 2px;
    color: var(--dim);
}

/* Wadah bar pemuatan progres */
.preloader-bar {
    width: 180px;
    height: 2px;
    background: var(--line);
    overflow: hidden;
    border-radius: 2px;
}

/* Animasi isi bar pemuatan progres */
.preloader-bar span {
    display: block;
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, var(--violet), var(--pink), var(--cyan));
    animation: loadbar 1.2s forwards;
}

/* Keyframes untuk animasi mengisi progres */
@keyframes loadbar {
    to {
        width: 100%;
    }
}

/* Jaring/latar belakang bergelombang */
.mesh {
    position: fixed;
    inset: 0;
    z-index: 0;
    overflow: hidden;
    pointer-events: none;
}

/* Efek bola cahaya di background (Mesh) */
.mesh .blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    opacity: .22;
}

/* Bola blur 1 (Kiri Atas) dengan animasi mengambang */
.b1 {
    width: 480px;
    height: 480px;
    background: var(--violet);
    top: -12%;
    left: -8%;
    animation: float1 26s ease-in-out infinite;
}

/* Bola blur 2 (Kanan Bawah) dengan animasi mengambang */
.b2 {
    width: 420px;
    height: 420px;
    background: var(--cyan);
    bottom: -15%;
    right: -10%;
    animation: float2 30s ease-in-out infinite;
}

/* Keyframes animasi mengambang bola blur 1 */
@keyframes float1 {
    0%,100% {
        transform: translate(0,0);
    }
    50% {
        transform: translate(60px,40px);
    }
}

/* Keyframes animasi mengambang bola blur 2 */
@keyframes float2 {
    10%,100% {
        transform: translate(0,0);
    }
    80% {
        transform: translate(-50px,-40px);
    }
}


.progress { position: fixed;
     top: 0; 
     left: 0; 
     height: 3px; 
     background: linear-gradient(90deg, var(--violet), var(--cyan)); 
     z-index: 200; width: 0%; transition: width 0.1s; }

main { position: relative; 
    z-index: 2; 
    width: 100%;
     overflow-x: hidden;
     }

nav { position: sticky; 
    top: 0; 
    z-index: 100;
     display: flex; 
     align-items: center; 
     justify-content: space-between; 
     padding: 20px 56px;
      background: rgba(10,14,23,.72); 
      backdrop-filter: blur(8px); 
      border-bottom: 1px solid var(--line);
     }

.logo { font-family: var(--font-display);
     font-weight: 700; 
     font-size: 18px;
      letter-spacing: -.5px;
     }

.logo span { background: linear-gradient(90deg, var(--violet), var(--cyan)); 
    -webkit-background-clip: text;
     background-clip: text; 
     color: transparent;
     }
     
.navlinks { display: flex; gap: 26px; font-family: var(--font-mono); font-size: 12.5px; color: var(--dim); align-items: center; }
.navlinks a { position: relative; color: var(--dim); }
.navlinks a::after { content: ""; position: absolute; left: 0; bottom: -5px; width: 0; height: 1px; background: var(--cyan); transition: width .3s; }
.navlinks a:hover::after { width: 100%; }
.navlinks a:hover { color: var(--text); }
.nav-admin-group { display: flex; align-items: center; gap: 10px; }
.nav-admin-btn { font-family: var(--font-mono); font-size: 11.5px; padding: 7px 14px; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px; transition: all .25s ease; cursor: pointer; text-decoration: none; }
.edit-btn { background: rgba(78,225,214,.1); border: 1px solid rgba(78,225,214,.35); color: var(--cyan); }
.edit-btn:hover { background: rgba(78,225,214,.2); border-color: var(--cyan); transform: translateY(-1px); color: #fff; }
.logout-btn { background: rgba(255,93,162,.1); border: 1px solid rgba(255,93,162,.35); color: var(--pink); }
.logout-btn:hover { background: rgba(255,93,162,.2); border-color: var(--pink); transform: translateY(-1px); color: #fff; }
.login-btn { background: var(--panel-2); border: 1px solid var(--line); color: var(--dim); }
.login-btn:hover { border-color: var(--cyan); color: var(--text); }
.navcta { font-family: var(--font-mono); font-size: 11.5px; padding: 10px 22px; border-radius: 20px; background: linear-gradient(90deg, var(--violet), var(--cyan)); color: #fff; border: none; display: inline-flex; align-items: center; gap: 8px; }
section { padding: 0 56px; position: relative; width: 100%; max-width: 1400px; margin: 0 auto; }
.hero { min-height: 92vh; display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 40px; align-items: center; padding-top: 40px; }
.hero-copy { min-width: 0; }
.eyebrow { font-family: var(--font-mono); font-size: 12px; color: var(--cyan); letter-spacing: 2px; text-transform: uppercase; margin-bottom: 22px; display: flex; align-items: center; gap: 10px; }
h1.title { font-family: var(--font-display); font-weight: 800; font-size: clamp(32px, 4.5vw, 68px); line-height: 1.05; letter-spacing: -1.5px; word-break: break-word; }
.grad-text { background: linear-gradient(90deg, #3763E0, #4E9BE0, #3763E0); background-size: 200% auto; -webkit-background-clip: text; background-clip: text; color: transparent; animation: gradmove 3.5s ease-in-out 3; }
@keyframes gradmove { to { background-position: 200% center; } }
.lede { font-family: var(--font-body); font-size: 15px; line-height: 1.75; color: var(--dim); max-width: 540px; margin: 22px 0 28px; }
.hero-cta { display: flex; gap: 16px; }
.btn { position: relative; font-family: var(--font-mono); font-size: 13px; padding: 14px 28px; border-radius: 8px; cursor: pointer; display: inline-block; }
.btn-primary { background: linear-gradient(90deg, var(--violet), var(--cyan)); color: #fff; border: none; }
.marquee-wrap { margin-top: 40px; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); padding: 16px 0; overflow: hidden; white-space: nowrap; width: 100%; }
.marquee { display: inline-flex; gap: 40px; animation: marquee 22s linear infinite; }
.marquee span { font-family: var(--font-display); font-size: 14px; font-weight: 600; color: var(--dim); display: flex; align-items: center; gap: 30px; }
.marquee span .plus { font-style: normal; color: var(--cyan); font-weight: 800; font-size: 18px; }
@keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
.hero-img-wrap { position: relative; width: 100%; max-width: 340px; margin: 0 auto; }
.hero-img { position: relative; width: 100%; aspect-ratio: 3/4; border-radius: 22px; overflow: hidden; background: linear-gradient(150deg,#F5B895,#E49B7A); border: 1px solid var(--line); }
.hero-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.hero-img .glow-ring { position: absolute; inset: -2px; border-radius: 23px; padding: 2px; background: linear-gradient(135deg, var(--violet), var(--cyan)); opacity: .55; -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
.hero-img .cap { position: absolute; left: 0; right: 0; bottom: 0; padding: 20px 22px; background: linear-gradient(0deg, rgba(10,10,18,.9), transparent); font-family: var(--font-mono); font-size: 11.5px; color: var(--dim); z-index: 2; text-align: center; }
.float-badge { position: absolute; background: var(--panel); border: 1px solid var(--line); border-radius: 14px; padding: 12px 16px; font-family: var(--font-mono); font-size: 11px; display: flex; align-items: center; gap: 10px; box-shadow: 0 20px 40px -20px rgba(0,0,0,.6); z-index: 5; white-space: nowrap; }
.fb1 { top: -16px; right: -16px; animation: bob 5s ease-in-out infinite; }
.fb2 { bottom: 60px; left: -22px; animation: bob 4s ease-in-out infinite 1s; }
@keyframes bob { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
.fb-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--cyan); box-shadow: 0 0 10px var(--cyan); }
.sec-head { display: flex; justify-content: space-between; align-items: flex-end; margin: 120px 0 44px; flex-wrap: wrap; gap: 20px; }
.sec-tag { font-family: var(--font-mono); font-size: 12px; color: var(--cyan); letter-spacing: 1px; margin-bottom: 14px; }
.sec-title { font-family: var(--font-display); font-weight: 800; font-size: 40px; letter-spacing: -1px; }
.sec-desc { font-family: var(--font-body); font-size: 14px; color: var(--dim); max-width: 280px; text-align: right; line-height: 1.6; }
.cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; perspective: 1200px; }
.card { background: var(--panel); border: 1px solid var(--line); border-radius: 16px; padding: 0 0 28px; position: relative; overflow: hidden; transform-style: preserve-3d; transition: transform .15s ease, border-color .3s; cursor: pointer; }
.card:hover { border-color: var(--cyan); transform: translateY(-5px); }
.card-img { height: 200px; position: relative; overflow: hidden; }
.card-img img { width: 100%; height: 100%; object-fit: cover; }
.card-body { padding: 22px 26px 0; position: relative; z-index: 2; }
.card .idx { font-family: var(--font-mono); font-size: 11px; color: var(--dim); text-transform: uppercase; font-weight: 600; letter-spacing: 1px; margin-bottom: 8px; }
.card h3 { font-family: var(--font-display); font-weight: 700; font-size: 19px; margin: 0 0 12px; color: #fff; }
.card p { font-family: var(--font-body); font-size: 13px; color: var(--dim); line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 0; }
.card .foot { margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; font-family: var(--font-mono); font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: var(--gold); }
.card .foot span:last-child { color: var(--dim); text-transform: none; font-weight: 500; font-family: var(--font-body); font-size: 12px; }
.about-panel { background: linear-gradient(160deg, var(--panel-2), var(--panel)); border: 1px solid var(--line); border-radius: 24px; padding: 56px; display: grid; grid-template-columns: 1fr 1.3fr; gap: 50px; align-items: center; }
.about-panel h2 { font-family: var(--font-display); font-weight: 800; font-size: 32px; letter-spacing: -1px; line-height: 1.25; margin-top: 14px; }
.slider { position: relative; width: 100%; overflow-x: auto; padding: 24px 0; margin-top: 8px; cursor: grab; user-select: none; scrollbar-width: none; }
.slider::-webkit-scrollbar { display: none; }
.slider:active { cursor: grabbing; }
.slider-track { display: flex; gap: 20px; width: max-content; }
.skill-chip { background: linear-gradient(160deg, var(--panel-2), var(--panel)); border: 1px solid var(--line); border-radius: 18px; padding: 26px; min-width: 250px; min-height: 140px; display: flex; flex-direction: column; justify-content: space-between; transition: border-color .3s, transform .3s; flex-shrink: 0; }
.skill-chip:hover { border-color: var(--cyan); transform: translateY(-4px); }
.skill-chip .ico { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; font-size: 15px; color: #fff; }
.skill-chip h3 { font-family: var(--font-display); font-size: 18px; font-weight: 700; margin-top: auto; }
.hint { text-align: center; font-family: var(--font-mono); font-size: 11px; color: var(--dim); margin-top: 8px; }
.contact-panel { max-width: 720px; margin: 0 auto; background: linear-gradient(160deg, var(--panel-2), var(--panel)); border: 1px solid var(--line); border-radius: 24px; padding: 52px; }
.field label { font-family: var(--font-mono); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--dim); display: block; margin-bottom: 8px; }
.field input, .field textarea { width: 100%; padding: 14px 16px; background: var(--bg); border: 1px solid var(--line); border-radius: 12px; color: var(--text); font-family: var(--font-body); font-size: 14px; transition: border-color .25s; }
.field input:focus, .field textarea:focus { outline: none; border-color: var(--cyan); }
footer { margin-top: 150px; padding: 80px 56px 40px; border-top: 1px solid var(--line); text-align: center; width: 100%; }
.footer-title { font-family: var(--font-display); font-weight: 800; font-size: clamp(30px, 5vw, 56px); letter-spacing: -1.5px; line-height: 1.1; }
.footer-cta { margin-top: 28px; display: inline-flex; padding: 16px 34px; border-radius: 30px; cursor: pointer; background: linear-gradient(90deg, var(--violet), var(--cyan)); background-size: 200% auto; font-family: var(--font-mono); font-size: 13px; color: #fff; font-weight: 700; border: none; transition: background-position .6s ease; text-decoration: none; }
.footer-cta:hover { background-position: 100% center; }
.footer-links { display: flex; justify-content: center; flex-wrap: wrap; gap: 24px; margin-top: 50px; font-family: var(--font-mono); font-size: 12px; color: var(--dim); }
.footer-links a:hover { color: var(--cyan); }
.footer-bottom { margin-top: 44px; font-family: var(--font-mono); font-size: 10.5px; color: var(--dim); }
.reveal { opacity: 0; transform: translateY(28px); transition: opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1); }
.reveal.in { opacity: 1; transform: none; }
.modal-overlay { position: fixed; inset: 0; z-index: 500; display: flex; align-items: center; justify-content: center; padding: 20px; opacity: 0; visibility: hidden; pointer-events: none; transition: opacity .3s ease; }
.modal-overlay.modal-active { opacity: 1; visibility: visible; pointer-events: auto; }
.modal-overlay .backdrop { position: absolute; inset: 0; background: rgba(10,10,18,.85); backdrop-filter: blur(6px); }
.modal-box { position: relative; z-index: 2; width: 100%; max-width: 640px; background: var(--panel); border: 1px solid var(--line); border-radius: 22px; overflow: hidden; transform: scale(.95); opacity: 0; transition: transform .3s ease, opacity .3s ease; }
.modal-box.modal-scale { transform: scale(1); opacity: 1; }
.modal-close { position: absolute; top: 16px; right: 16px; width: 38px; height: 38px; border-radius: 50%; background: rgba(0,0,0,.4); display: flex; align-items: center; justify-content: center; color: #fff; z-index: 3; border: none; cursor: pointer; transition: background .25s; }
.modal-close:hover { background: var(--cyan); color: #000; }
.modal-img { width: 100%; height: 240px; background: var(--panel-2); }
.modal-img img { width: 100%; height: 100%; object-fit: cover; }
.modal-content { padding: 36px 40px; }
.modal-cat { font-family: var(--font-mono); font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--gold); }
.modal-title { font-family: var(--font-display); font-weight: 700; font-size: 26px; margin: 12px 0 16px; }
.modal-desc { color: var(--dim); font-size: 14px; line-height: 1.75; }
.spam-modal { max-width: 420px; text-align: center; padding: 44px 36px; }
.spam-icon { width: 64px; height: 64px; margin: 0 auto 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; background: rgba(255,187,66,0.12); color: #FFBB42; border: 1px solid rgba(255,187,66,0.3); }
.spam-title { font-family: var(--font-display); font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 10px; }
.spam-desc { color: var(--dim); font-size: 13.5px; line-height: 1.65; margin-bottom: 24px; }
.spam-timer { background: var(--bg); border: 1px solid var(--line); border-radius: 14px; padding: 16px; margin-bottom: 26px; }
.spam-timer-num { display: block; font-family: var(--font-mono); font-size: 26px; font-weight: 800; color: var(--cyan); letter-spacing: .5px; }
.spam-timer-label { display: block; font-family: var(--font-body); font-size: 11.5px; color: var(--dim); margin-top: 4px; }
.spam-btn { width: 100%; background: var(--panel-2); border: 1px solid var(--line); color: var(--text); cursor: pointer; }
.spam-btn:hover { border-color: var(--cyan); color: var(--cyan); }
.timeline-zigzag { position: relative; max-width: 1000px; margin: 40px auto; padding: 20px 0; }
.timeline-zigzag::after { content: ''; position: absolute; width: 2px; background: linear-gradient(180deg, var(--cyan), var(--violet)); top: 0; bottom: 0; left: 50%; margin-left: -1px; }
.tz-item { padding: 10px 40px; position: relative; width: 50%; margin-bottom: 20px; }
.tz-item:nth-child(odd) { left: 0; text-align: right; }
.tz-item:nth-child(even) { left: 50%; text-align: left; }
.tz-item::after { content: ''; position: absolute; width: 22px; height: 22px; right: -11px; background: var(--bg); border: 4px solid var(--cyan); top: 25px; border-radius: 50%; z-index: 1; transition: 0.3s; }
.tz-item:nth-child(even)::after { left: -11px; }
.tz-item:hover::after { background: var(--cyan); box-shadow: 0 0 15px var(--cyan); }
.tz-content { padding: 24px 30px; background: rgba(0,0,0,0.15); border: 1px solid var(--line); border-radius: 16px; position: relative; transition: 0.3s; cursor: pointer; }
.tz-content:hover { border-color: var(--cyan); transform: translateY(-5px); background: rgba(255,255,255,0.03); }
.tz-role { font-family: var(--font-display); font-weight: 700; font-size: 18px; color: #fff; margin-bottom: 5px; }
.tz-org { font-family: var(--font-mono); font-size: 11.5px; color: var(--gold); margin-bottom: 12px; }
.tz-desc { color: var(--dim); font-size: 13px; line-height: 1.7; }
.btn-toggle-skill, .btn-toggle-org { background: rgba(78, 225, 214, 0.05); border: 1px solid var(--cyan); color: var(--cyan); padding: 12px 28px; border-radius: 30px; font-family: var(--font-mono); font-size: 13px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; margin-top: 35px; }
.btn-toggle-skill:hover, .btn-toggle-org:hover { background: var(--cyan); color: #000; box-shadow: 0 0 15px rgba(78, 225, 214, 0.4); }

@media (max-width: 900px) { section, nav, footer { padding-left: 20px; padding-right: 20px; } .navlinks { display: none; } .hero { grid-template-columns: 1fr; min-height: auto; padding-bottom: 40px; } .hero-img-wrap { order: -1; margin-bottom: 20px; max-width: 320px; } .cards { grid-template-columns: 1fr; } .about-panel { grid-template-columns: 1fr; padding: 32px; } .sec-desc { text-align: left; } }
@media screen and (max-width: 768px) { .timeline-zigzag::after { left: 31px; } .tz-item { width: 100%; padding-left: 70px; padding-right: 0; text-align: left !important; left: 0 !important; } .tz-item::after { left: 20px !important; } }
</style>