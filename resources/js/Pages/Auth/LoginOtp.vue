<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useForm, Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    otpTime: Number
});

const form = useForm({
    otp: '',
});

const resendForm = useForm({});
const customError = ref(''); 
let errorTimer = null;
const countdown = ref(0); 
let countdownInterval = null;

const page = usePage();

const startInterval = () => {
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--;
        } else {
            clearInterval(countdownInterval);
        }
    }, 1000);
};

const triggerErrorTimer = (message) => {
    customError.value = message;
    if (errorTimer) clearTimeout(errorTimer);
    errorTimer = setTimeout(() => {
        customError.value = '';
        form.clearErrors();
        resendForm.clearErrors();
    }, 5000);
};

const submit = () => {
    customError.value = ''; 
    form.clearErrors();

    form.post('/login/otp', {
        onFinish: () => { form.otp = ''; },
        onError: (errors) => {
            if (errors.otp) triggerErrorTimer(errors.otp);
        }
    });
};

const requestResend = () => {
    if (countdown.value > 0) return; 
    customError.value = '';

    resendForm.post('/login/otp/resend', {
        onSuccess: () => {
            countdown.value = 60;
            sessionStorage.setItem('otp_timer_start', Date.now().toString());
            if (props.otpTime) sessionStorage.setItem('server_otp_time', props.otpTime.toString());
            startInterval(); 
        },
        onError: (errors) => {
            if (errors.otp) triggerErrorTimer(errors.otp);
        }
    });
};

let httpListener = null;
let networkListener = null;

onMounted(() => {
    const savedServerTime = sessionStorage.getItem('server_otp_time');

    if (props.otpTime && savedServerTime !== props.otpTime.toString()) {
        countdown.value = 60;
        sessionStorage.setItem('otp_timer_start', Date.now().toString());
        sessionStorage.setItem('server_otp_time', props.otpTime.toString());
    } else {
        const lastStart = sessionStorage.getItem('otp_timer_start');
        if (lastStart) {
            const secondsPassed = Math.floor((Date.now() - parseInt(lastStart)) / 1000);
            if (secondsPassed < 60) {
                countdown.value = 60 - secondsPassed;
            } else {
                countdown.value = 0;
            }
        } else {
            countdown.value = 0;
        }
    }
    
    startInterval(); 

    httpListener = router.on('httpException', (event) => {
        if (event.detail.response?.status === 429) {
            event.preventDefault();
            triggerErrorTimer('Terlalu banyak permintaan! silahkan coba di lain waktu');
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
    if (countdownInterval) clearInterval(countdownInterval);
});
</script>

<template>
    <Head title="Verifikasi OTP - Admin Login" />
    
    <div class="mesh">
        <div class="blob b1"></div>
        <div class="blob b2"></div>
    </div>
    <div class="noise"></div>

    <div class="card">
        <div class="titlebar">
            <span class="titlebar-label">Yanzewty Admin</span>

            <Link href="/login" class="inside-back-btn" title="Kembali ke Login">
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
                        <i class="fas fa-envelope-open-text"></i> Kode berhasil dikirim
                    </div>
                </div>

                <div class="brand-bottom">
                    <h1 class="brand-headline">
                        Satu langkah lagi <br>
                        <span class="accent-text">untuk masuk.</span>
                    </h1>
                </div>
            </aside>

            <!-- PANEL KANAN -->
            <div class="form-panel">
                <div class="body">
                    <div class="icon-wrap"><i class="fas fa-shield-alt"></i></div>

                    <h2>Verifikasi OTP</h2>
                    <p class="sub">masukkan 6 digit dari email Anda</p>

                    <div v-if="customError" class="error">
                        <i class="fas fa-exclamation-circle" style="margin-top:2px"></i>
                        <span>{{ customError }}</span>
                    </div>

                    <div v-if="$page.props.flash?.success_msg" class="success">
                        <i class="fas fa-check-circle" style="margin-top:2px"></i>
                        <span>{{ $page.props.flash.success_msg }}</span>
                    </div>

                    <form @submit.prevent="submit" class="form-transition">
                        <div>
                            <div class="input-wrap">
                                <i class="fas fa-key left"></i>
                                <input 
                                    type="text" 
                                    v-model="form.otp" 
                                    required 
                                    maxlength="6" 
                                    placeholder="••••••" 
                                    autocomplete="one-time-code"
                                    class="otp-input"
                                >
                            </div>
                        </div>

                        <!-- Tombol Verifikasi Utama -->
                        <button type="submit" class="submit-btn" :disabled="form.processing">
                            <i class="fas fa-check-circle"></i> 
                            {{ form.processing ? 'Memverifikasi...' : 'Verifikasi & Masuk' }}
                        </button>
                    </form>

                    <!-- Fitur Kirim Ulang Kode -->
                    <div class="resend-wrap">
                        <span v-if="countdown > 0" class="timer-text">
                            Belum terima email? Tunggu <b>{{ countdown }} detik</b>
                        </span>
                        
                        <button 
                            v-else 
                            @click="requestResend" 
                            type="button" 
                            class="resend-btn"
                            :disabled="resendForm.processing"
                        >
                            <i class="fas fa-sync-alt" :class="{'fa-spin': resendForm.processing}"></i> 
                            {{ resendForm.processing ? 'Mengirim Ulang...' : 'Kirim Ulang Kode OTP' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Bagian 1: Rapi ke bawah (Neat) dengan penjelasan */

/* Variabel warna global untuk tema panel */
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

/* Memastikan padding tidak merusak lebar kotak */
div {
    box-sizing: border-box;
}

/* Latar belakang jaring/gelap */
.mesh {
    position: fixed;
    inset: 0;
    z-index: 0;
    overflow: hidden;
    background: var(--bg);
}

/* Efek bola cahaya di latar belakang */
.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    opacity: .10;
}

/* Posisi bola cahaya pertama (Kanan atas) */
.b1 {
    width: 380px;
    height: 380px;
    background: #4C6FE0;
    top: -15%;
    right: -10%;
}

/* Posisi bola cahaya kedua (Kiri bawah) */
.b2 {
    width: 320px;
    height: 320px;
    background: #3A56B8;
    bottom: -15%;
    left: -10%;
}

/* Tekstur titik-titik (noise) di latar belakang */
.noise {
    position: fixed;
    inset: 0;
    z-index: 1;
    opacity: .02;
    pointer-events: none;
}

/* Kartu pembungkus form utama */
.card {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 2;
    width: 100%;
    max-width: 800px;
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 30px 60px -20px rgba(0,0,0,.7);
    color: var(--text);
    font-family: 'Inter', sans-serif;
}

/* Area baris judul di atas kartu */
.titlebar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-bottom: 1px solid var(--line);
    background: rgba(0,0,0,.15);
}

/* Teks identitas di pojok kiri atas */
.titlebar-label {
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    color: var(--dim);
    letter-spacing: .3px;
}

/* Tombol kecil kembali ke login */
.inside-back-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--dim);
    background: rgba(35, 40, 51, 0.4);
    border: 1px solid var(--line);
    padding: 5px 10px;
    border-radius: 8px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    text-decoration: none;
    transition: 0.2s;
}

/* Efek saat tombol kembali disorot */
.inside-back-btn:hover {
    background: rgba(76, 111, 224, 0.14);
    border-color: var(--accent);
    color: #fff;
}




.content { display: flex; align-items: stretch; min-height: 520px; }
.brand-panel { position: relative; width: 44%; flex-shrink: 0; overflow: hidden; padding: 40px 32px; display: flex; flex-direction: column; justify-content: flex-start; gap: 40px; background: var(--panel-alt); border-right: 1px solid var(--line); }
.brand-blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: .12; pointer-events: none; }
.bb1 { width: 260px; height: 260px; background: #4C6FE0; top: -60px; left: -60px; }
.bb2 { width: 220px; height: 220px; background: #3A56B8; bottom: -60px; right: -50px; }
.brand-top { display: flex; flex-direction: column; gap: 16px; position: relative; z-index: 2; }
.logo-mark { display: flex; align-items: center; gap: 10px; }
.logo-icon { width: 38px; height: 38px; border-radius: 10px; background: var(--accent-grad); display: flex; align-items: center; justify-content: center; font-family: 'Sora', sans-serif; font-weight: 800; font-size: 17px; color: #fff; }
.logo-text { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 19px; letter-spacing: .2px; color: var(--text); }
.badge-pill { align-self: flex-start; display: flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; background: rgba(76, 111, 224, 0.10); border: 1px solid rgba(76, 111, 224, 0.28); font-family: 'JetBrains Mono', monospace; font-size: 10.5px; color: #9DB0F0; }
.brand-bottom { position: relative; z-index: 2; }
.brand-headline { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 28px; line-height: 1.25; margin: 0 0 14px; color: var(--text); }
.accent-text { color: #7B94EC; }
.form-panel { flex: 1; min-width: 0; display: flex; align-items: center; justify-content: center; background: var(--panel); }
.body { padding: 40px 36px; width: 100%; }
.icon-wrap { width: 50px; height: 50px; border-radius: 14px; background: var(--accent-grad); display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 20px; color: #ffffff; }
h2 { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 22px; text-align: center; margin: 0; color: var(--text); }
.sub { font-family: 'JetBrains Mono', monospace; font-size: 11.5px; color: var(--dim); text-align: center; margin-top: 6px; }
.error, .success { margin-top: 20px; padding: 13px 16px; border-radius: 12px; font-size: 12.5px; display: flex; gap: 10px; align-items: flex-start; }
.error { background: rgba(214, 82, 82, .08); border: 1px solid rgba(214, 82, 82, .3); color: #E07A7A; }
.success { background: rgba(76, 111, 224, .08); border: 1px solid rgba(76, 111, 224, .3); color: #9DB0F0; }
.form-transition { margin-top: 24px; display: flex; flex-direction: column; gap: 14px; }
.input-wrap { position: relative; }
.input-wrap i.left { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--dim); font-size: 14px; }
.otp-input { width: 100%; padding: 14px 14px 14px 45px; background: var(--bg); border: 1px solid var(--line); border-radius: 12px; color: var(--accent); font-size: 18px; font-weight: bold; letter-spacing: 6px; box-sizing: border-box; text-align: center; transition: all 0.2s ease; }
.otp-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(76, 111, 224, 0.14); }
.submit-btn { width: 100%; padding: 14px; border: none; border-radius: 12px; cursor: pointer; background: var(--accent-grad); color: #fff; font-family: 'JetBrains Mono', monospace; font-size: 13px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25); margin-top: 6px; }
.submit-btn:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.submit-btn:active:not(:disabled) { transform: translateY(1px); }
.submit-btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
.resend-wrap { margin-top: 24px; text-align: center; }
.timer-text { font-family: 'JetBrains Mono', monospace; font-size: 11.5px; color: var(--dim); }
.timer-text b { color: var(--text); }
.resend-btn { background: transparent; border: 1px solid var(--line); border-radius: 8px; color: var(--accent); padding: 8px 16px; font-family: 'JetBrains Mono', monospace; font-size: 11.5px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
.resend-btn:hover:not(:disabled) { background: rgba(76, 111, 224, 0.1); border-color: var(--accent); }
.resend-btn:disabled { opacity: 0.5; cursor: not-allowed; }

@media (max-width: 820px) { .card { max-width: 420px; } .brand-panel { display: none; } .content { min-height: 0; } .body { padding: 32px 24px; } }
</style>