<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useForm, Head, Link, router } from '@inertiajs/vue3';

const loginMode = ref('password');

const passwordForm = useForm({
    email: '',
    password: '',
});

/* form OTP sekarang punya tampungan token */
const otpForm = useForm({
    email: '',
    recaptcha_token: ''
});

const showPassword = ref(false);
const customError = ref(''); 
let errorTimer = null;

const isDragging = ref(false);
const startX = ref(0);
const recaptchaWidgetId = ref(null);

/* Fungsi menggambar kotak CAPTCHA (tema gelap) */
const initRecaptcha = () => {
    if (window.grecaptcha && window.grecaptcha.render) {
        const container = document.getElementById('recaptcha-container');
        if (container && container.innerHTML === '') {
            recaptchaWidgetId.value = window.grecaptcha.render('recaptcha-container', {
                'sitekey': import.meta.env.VITE_RECAPTCHA_SITE_KEY,
                'theme': 'dark', 
                'callback': (token) => { 
                    otpForm.recaptcha_token = token; 
                    customError.value = ''; 
                },
                'expired-callback': () => { 
                    otpForm.recaptcha_token = ''; 
                }
            });
        }
    } else {
        setTimeout(initRecaptcha, 200);
    }
};

/* Deteksi: Jika beralih ke form OTP, langsung gambar kotaknya */
watch(loginMode, async (newMode) => {
    if (newMode === 'otp') {
        await nextTick();
        initRecaptcha();
    }
});

const startDrag = (e) => {
    isDragging.value = true;
    startX.value = e.clientX || e.touches?.[0]?.clientX || 0;
};

const onDrag = (e) => {
    if (!isDragging.value) return;
    const currentX = e.clientX || e.touches?.[0]?.clientX || 0;
    const diff = currentX - startX.value;
    
    if (diff > 30) {
        loginMode.value = 'otp';
        customError.value = '';
        isDragging.value = false;
    } else if (diff < -30) {
        loginMode.value = 'password';
        customError.value = '';
        isDragging.value = false;
    }
};

const stopDrag = () => {
    isDragging.value = false;
};

const triggerErrorTimer = (message) => {
    customError.value = message;
    if (errorTimer) clearTimeout(errorTimer);
    errorTimer = setTimeout(() => {
        customError.value = '';
        passwordForm.clearErrors();
        otpForm.clearErrors();
    }, 4000);
};

const submitPasswordLogin = () => {
    customError.value = ''; 
    passwordForm.clearErrors();

    passwordForm.post('/login', {
        onFinish: () => { passwordForm.password = ''; },
        onError: (errors) => {
            if (errors.email) triggerErrorTimer(errors.email);
        }
    });
};

const submitOtpRequest = () => {
    customError.value = ''; 
    otpForm.clearErrors();

    /* Peringatan jika user belum centang "I'm not a robot" */
    if (!otpForm.recaptcha_token) {
        triggerErrorTimer('Silakan selesaikan verifikasi keamanan (CAPTCHA) terlebih dahulu.');
        return;
    }

    otpForm.post('/login/request-otp', {
        onSuccess: () => {
            otpForm.reset();
            if (window.grecaptcha && recaptchaWidgetId.value !== null) {
                window.grecaptcha.reset(recaptchaWidgetId.value);
            }
        },
        onError: (errors) => {
            if (errors.email) triggerErrorTimer(errors.email);
            if (errors.recaptcha_token) triggerErrorTimer(errors.recaptcha_token);
            if (window.grecaptcha && recaptchaWidgetId.value !== null) {
                window.grecaptcha.reset(recaptchaWidgetId.value);
                otpForm.recaptcha_token = '';
            }
        }
    });
};

let httpListener = null;
let networkListener = null;

onMounted(() => {
    /* Masukkan script resmi Google secara asinkron (tidak bikin web lemot) */
    if (!document.getElementById('recaptcha-script')) {
        const script = document.createElement('script');
        script.id = 'recaptcha-script';
        script.src = 'https://www.google.com/recaptcha/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    httpListener = router.on('httpException', (event) => {
        if (event.detail.response?.status === 429) {
            event.preventDefault();
            triggerErrorTimer('Terlalu banyak percobaan! Silakan tunggu sebentar.');
        }
    });

    networkListener = router.on('networkError', (event) => {
        event.preventDefault(); 
        triggerErrorTimer('Koneksi terganggu.');
    });
});

onUnmounted(() => {
    if (httpListener) httpListener();
    if (networkListener) networkListener();
});
</script>

<template>
    <Head title="Admin Login - Portofolio" />
    
    <div class="mesh">
        <div class="blob b1"></div>
        <div class="blob b2"></div>
    </div>
    <div class="noise"></div>

    <div class="card">
        <div class="titlebar">
            <span class="titlebar-label">Yanzewty-Admin</span>

            <Link href="/" class="inside-back-btn" title="Kembali ke Portofolio">
                <i class="fas fa-arrow-left"></i> Kembali
            </Link>
        </div>

        <div class="content">
            <!-- PANEL KIRI -->
            <aside class="brand-panel">
                <div class="brand-blob bb1"></div>
                <div class="brand-blob bb2"></div>
                <div class="comet comet-1"></div>
                <div class="comet comet-2"></div>

                <div class="brand-top">
                    <div class="logo-mark">
                        <div class="logo-icon">Y</div>
                        <span class="logo-text">Yanzewty</span>
                    </div>
                    <div class="badge-pill">
                        <i class="fas fa-shield-alt"></i> Akses Admin
                    </div>
                </div>

                <div class="brand-bottom">
                    <h1 class="brand-headline">
                        Kelola karya. <br>
                        <span class="accent-text">Kendalikan cerita.</span>
                    </h1>
                    <p class="brand-sub">Masuk ke panel admin untuk mengatur seluruh konten portofolio.</p>
                </div>
            </aside>

            <!-- PANEL KANAN -->
            <div class="form-panel">
                <div class="body">
                    <h2>Admin Login</h2>
                    <p class="sub">silakan masuk untuk mengelola portofolio</p>

                    <div 
                        class="mode-switch-wrap"
                        @mousedown="startDrag"
                        @mousemove="onDrag"
                        @mouseup="stopDrag"
                        @mouseleave="stopDrag"
                        @touchstart="startDrag"
                        @touchmove="onDrag"
                        @touchend="stopDrag"
                    >
                        <div class="mode-slider-indicator" :class="loginMode"></div>
                        
                        <button 
                            type="button" 
                            :class="['mode-btn', { active: loginMode === 'password' }]" 
                            @click="loginMode = 'password'; customError = '';"
                        >
                            <i class="fas fa-key"></i> Password
                        </button>
                        <button 
                            type="button" 
                            :class="['mode-btn', { active: loginMode === 'otp' }]" 
                            @click="loginMode = 'otp'; customError = '';"
                        >
                            <i class="fas fa-shield-alt"></i> Kode OTP
                        </button>
                    </div>

                    <div v-if="customError" class="error">
                        <i class="fas fa-exclamation-circle" style="margin-top:2px"></i>
                        <span>{{ customError }}</span>
                    </div>

                    <div v-if="$page.props.flash?.success_msg" class="success">
                        <i class="fas fa-check-circle" style="margin-top:2px"></i>
                        <span>{{ $page.props.flash.success_msg }}</span>
                    </div>

                    <!-- FORM PASSWORD (TIDAK ADA CAPTCHA) -->
                    <form v-if="loginMode === 'password'" @submit.prevent="submitPasswordLogin" class="form-transition">
                        <div>
                            <label>Email</label>
                            <div class="input-wrap">
                                <i class="fas fa-envelope left"></i>
                                <input type="email" v-model="passwordForm.email" required placeholder="email@gmail.com">
                            </div>
                        </div>

                        <div>
                            <label>Password</label>
                            <div class="input-wrap">
                                <i class="fas fa-key left"></i>
                                <input :type="showPassword ? 'text' : 'password'" v-model="passwordForm.password" required placeholder="••••••••" style="padding-right:42px;">
                                <button type="button" class="eye-btn" @click="showPassword = !showPassword">
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn" :disabled="passwordForm.processing">
                            <i class="fas fa-sign-in-alt"></i> 
                            {{ passwordForm.processing ? 'Memproses...' : 'Masuk Admin' }}
                        </button>
                    </form>

                    <!-- FORM OTP (DENGAN CAPTCHA) -->
                    <form v-else @submit.prevent="submitOtpRequest" class="form-transition">
                        <div>
                            <label>Email Admin</label>
                            <div class="input-wrap">
                                <i class="fas fa-envelope left"></i>
                                <input type="email" v-model="otpForm.email" required placeholder="email@gmail.com">
                            </div>
                        </div>

                        <p class="otp-hint">Sistem akan mengirimkan 6 digit kode verifikasi instan ke email terdaftar Anda.</p>

                        <!-- Tempat reCAPTCHA akan digambar oleh Google -->
                        <div id="recaptcha-container" class="recaptcha-wrap"></div>

                        <button type="submit" class="submit-btn" :disabled="otpForm.processing">
                            <i class="fas fa-paper-plane"></i> 
                            {{ otpForm.processing ? 'Mengirim Kode...' : 'Kirim Kode OTP' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
:global(:root) {
    --bg: #0B0E14;
    --panel: #12151C;
    --panel-alt: #14171F;
    --text: #E7E9EE;
    --dim: #8A93A3;
    --line: #232833;
    --accent: #4C6FE0;
    --accent-grad: linear-gradient(135deg, #4C6FE0, #3A56B8);
}

div { box-sizing: border-box; }

.mesh { position:fixed; inset:0; z-index:0; overflow:hidden; background: var(--bg); }
.blob { position:absolute; border-radius:50%; filter:blur(70px); opacity:.10; }
.b1 { width:380px; height:380px; background:#4C6FE0; top:-15%; right:-10%; }
.b2 { width:320px; height:320px; background:#3A56B8; bottom:-15%; left:-10%; }
.noise { position:fixed; inset:0; z-index:1; opacity:.02; pointer-events:none; }

.card { 
    position:absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
    z-index:2; width:100%; max-width:800px; 
    background: var(--panel); 
    border:1px solid var(--line); border-radius:20px; overflow:hidden; 
    box-shadow:0 30px 60px -20px rgba(0,0,0,.7); 
    color: var(--text); font-family: 'Inter', sans-serif;
}

.titlebar { 
    display:flex; align-items:center; justify-content:space-between; 
    padding:12px 18px; border-bottom:1px solid var(--line); background:rgba(0,0,0,.15); 
}
.titlebar-label { font-family:'JetBrains Mono',monospace; font-size:11px; color:var(--dim); letter-spacing:.3px; }

.inside-back-btn {
    display: flex; align-items: center; gap: 6px; 
    color: var(--dim); background: rgba(35, 40, 51, 0.4); 
    border: 1px solid var(--line); padding: 5px 10px; border-radius: 8px; 
    font-family: 'JetBrains Mono', monospace; font-size: 11px; text-decoration: none; transition: 0.2s;
}
.inside-back-btn:hover { background: rgba(76, 111, 224, 0.14); border-color: var(--accent); color: #fff; }

.content { display:flex; align-items:stretch; min-height: 520px; }

.brand-panel {
    position: relative; width: 44%; flex-shrink: 0; overflow: hidden; padding: 40px 32px;
    display: flex; flex-direction: column; justify-content: flex-start; gap: 60px;
    background: var(--panel-alt); border-right: 1px solid var(--line);
}
.brand-blob { position:absolute; border-radius:50%; filter:blur(80px); opacity:.12; pointer-events:none; }
.bb1 { width:260px; height:260px; background:#4C6FE0; top:-60px; left:-60px; }
.bb2 { width:220px; height:220px; background:#3A56B8; bottom:-60px; right:-50px; }

.brand-top { display:flex; flex-direction:column; gap:16px; position:relative; z-index:2; }
.logo-mark { display:flex; align-items:center; gap:10px; }
.logo-icon {
    width:38px; height:38px; border-radius:10px; background: var(--accent-grad);
    display:flex; align-items:center; justify-content:center;
    font-family:'Sora',sans-serif; font-weight:800; font-size:17px; color:#fff;
}
.logo-text { font-family:'Sora',sans-serif; font-weight:700; font-size:19px; letter-spacing:.2px; color:var(--text); }

.badge-pill {
    align-self: flex-start; display:flex; align-items:center; gap:8px;
    padding:8px 14px; border-radius:999px; background: rgba(76, 111, 224, 0.10);
    border: 1px solid rgba(76, 111, 224, 0.28); font-family:'JetBrains Mono',monospace; font-size:10.5px; color:#9DB0F0;
}

.brand-bottom { position:relative; z-index:2; }
.brand-headline { font-family:'Sora',sans-serif; font-weight:700; font-size:28px; line-height:1.25; margin:0 0 14px; color:var(--text); }
.accent-text { color: #7B94EC; }
.brand-sub { font-family:'JetBrains Mono',monospace; font-size:11.5px; line-height:1.7; color:var(--dim); max-width: 300px; }

.form-panel { flex: 1; min-width: 0; display:flex; align-items: center; justify-content: center; background: var(--panel); }
.body { padding: 40px 36px; width:100%; }

h2 { font-family:'Sora',sans-serif; font-weight:700; font-size:22px; text-align:center; margin:0; color: var(--text); }
.sub { font-family:'JetBrains Mono',monospace; font-size:11.5px; color:var(--dim); text-align:center; margin-top:6px; }

.mode-switch-wrap {
    display: flex; background: var(--bg); border: 1px solid var(--line);
    border-radius: 12px; padding: 4px; margin-top: 22px; position: relative; cursor: grab; user-select: none;
}
.mode-switch-wrap:active { cursor: grabbing; }

.mode-slider-indicator {
    position: absolute; top: 4px; bottom: 4px; width: calc(50% - 4px);
    background: var(--accent-grad); border-radius: 9px; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.mode-slider-indicator.otp { transform: translateX(100%); }

.mode-btn {
    flex: 1; background: transparent; border: none; padding: 10px; border-radius: 9px;
    font-family: 'JetBrains Mono', monospace; font-size: 11.5px; color: var(--dim);
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; position: relative; z-index: 2; transition: color 0.3s ease;
}
.mode-btn.active { color: #ffffff; font-weight: 600; }

.error, .success { margin-top:18px; padding:12px 14px; border-radius:10px; font-size:12px; display:flex; gap:8px; align-items:flex-start; }
.error { background:rgba(214, 82, 82, .08); border:1px solid rgba(214, 82, 82, .3); color:#E07A7A; }
.success { background:rgba(76, 111, 224, .08); border:1px solid rgba(76, 111, 224, .3); color:#9DB0F0; }

.form-transition { margin-top:20px; display:flex; flex-direction:column; gap:14px; }

label { 
    font-family:'JetBrains Mono',monospace; font-size:11px; text-transform:uppercase; 
    letter-spacing:1px; color:var(--dim); display:block; margin-bottom:6px; 
}

.input-wrap { position:relative; }
.input-wrap i.left { position:absolute; left:15px; top:50%; transform:translateY(-50%); color:var(--dim); font-size:13px; }

.input-wrap input { 
    width:100%; padding:12px 14px 12px 42px; background:var(--bg); border:1px solid var(--line); border-radius:10px; 
    color: var(--text); font-size:13.5px; box-sizing: border-box; transition: all 0.2s ease; 
}
.input-wrap input:focus { outline:none; border-color:var(--accent); box-shadow: 0 0 0 3px rgba(76, 111, 224, 0.14); }

.eye-btn { 
    position:absolute; right:14px; top:50%; transform:translateY(-50%); 
    background:none; border:none; color:var(--dim); cursor:pointer; font-size:13px; 
}
.eye-btn:hover { color:var(--text); }

.otp-hint {
    font-size: 11.5px; color: var(--dim); line-height: 1.5;
    font-family: 'JetBrains Mono',monospace; margin: -2px 0 0 2px;
}

/* Penyesuaian jarak kotak CAPTCHA agar presisi di tengah form */
/* Penyesuaian jarak dan manipulasi ukuran kotak CAPTCHA */
.recaptcha-wrap {
    display: flex;
    justify-content: center;
    margin-top: 4px;
    margin-bottom: 2px;
    transform: scaleX(1.05) scaleY(0.9); 
    transform-origin: center;
}

.submit-btn { 
    width:100%; padding:13px; border:none; border-radius:10px; cursor:pointer; background: var(--accent-grad); color:#fff; 
    font-family:'JetBrains Mono',monospace; font-size:12.5px; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px; 
    transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25); margin-top: 4px;
}
.submit-btn:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.submit-btn:active:not(:disabled) { transform: translateY(1px); }
.submit-btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

@media (max-width: 820px) {
    .card { max-width: 420px; }
    .brand-panel { display: none; }
    .content { min-height: 0; }
    .body { padding: 32px 24px; }
}
</style>