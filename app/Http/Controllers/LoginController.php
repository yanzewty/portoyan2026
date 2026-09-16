<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http; 
use App\Models\User;
use Inertia\Inertia;

class LoginController extends Controller
{
    // Bikin tampilan email buat OTP
    private function getHtmlEmailDesign($otp)
    {
        return "
        <div style='background-color:#0B0E14; padding:40px 20px; font-family:Arial, sans-serif;'>
            <div style='max-width:450px; margin:0 auto; background:#12151C; border:1px solid #232833; border-radius:16px; padding:40px 30px; text-align:center;'>
                <h2 style='color:#E7E9EE; margin-top:0; font-size:22px;'>Verifikasi Login</h2>
                <p style='color:#8A93A3; font-size:14px; line-height:1.6; margin-bottom:25px;'>
                    Sistem mendeteksi upaya masuk ke Panel Admin Portofolio. Silakan gunakan kode verifikasi (OTP) berikut untuk melanjutkan.
                </p>
                <div style='background:linear-gradient(135deg, #4C6FE0, #3A56B8); padding:15px 30px; border-radius:12px; display:inline-block; margin-bottom:25px; box-shadow:0 4px 15px rgba(76, 111, 224, 0.4);'>
                    <span style='color:#ffffff; font-size:32px; font-weight:bold; letter-spacing:10px;'>{$otp}</span>
                </div>
                <p style='color:#8A93A3; font-size:12px; margin-bottom:0;'>
                    Kode ini hanya berlaku <strong>5 menit</strong>. Jangan berikan kepada siapapun.
                </p>
            </div>
        </div>
        ";
    }

    // Nampilin form login
    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }

    // Proses login biasa
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Kalo user ga ada atau password salah, balikin error
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Email atau password salah!']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended('/admin');
    }

    // Minta kode OTP
    public function requestOtp(Request $request)
    {
        // Cek jeda waktu biar ga spam
        if (session()->has('login_otp_time')) {
            $timePassed = time() - session('login_otp_time');
            if ($timePassed < 60) {
                return back()->withErrors(['email' => 'Harap tunggu ' . (60 - $timePassed) . ' detik sebelum mengirim ulang kode.']);
            }
        }

        $request->validate([
            'email' => 'required|email',
            'recaptcha_token' => 'required'
        ], [
            'recaptcha_token.required' => 'Silakan centang verifikasi keamanan terlebih dahulu.'
        ]);

        // Verifikasi dari mbah Google (reCAPTCHA)
        $response = Http::withoutVerifying()->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $request->recaptcha_token,
            'remoteip' => $request->ip()
        ]);

        if (!$response->json('success')) {
            return back()->withErrors(['email' => 'Validasi CAPTCHA gagal atau kadaluarsa. Silakan muat ulang halaman.']);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email tidak terdaftar di sistem!']);
        }

        // Bikin 6 digit angka random
        $otp = random_int(100000, 999999);

        // Simpan ke session
        session([
            'login_user_id' => $user->id,
            'login_otp' => $otp,
            'login_otp_time' => time()
        ]);

        // Coba kirim email
        try {
            Mail::html($this->getHtmlEmailDesign($otp), function ($message) use ($user) {
                $message->to($user->email)->subject('Kode Verifikasi Login Admin Portofolio');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Gagal mengirim email OTP. Cek koneksi SMTP!']);
        }

        return redirect()->route('login.otp')->with('success_msg', 'Kode verifikasi telah dikirim ke email!');
    }

    // Kirim ulang kode kalo belum masuk
    public function resendOtp(Request $request)
    {
        if (!session()->has('login_user_id')) {
            return back()->withErrors(['otp' => 'Sesi tidak valid, silakan login ulang dari awal.']);
        }

        $user = User::find(session('login_user_id'));
        $otp = random_int(100000, 999999);

        session([
            'login_otp' => $otp,
            'login_otp_time' => time()
        ]);

        try {
            Mail::html($this->getHtmlEmailDesign($otp), function ($message) use ($user) {
                $message->to($user->email)->subject('Resend: Kode Verifikasi Baru');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['otp' => 'Gagal mengirim ulang kode.']);
        }

        return back()->with('success_msg', 'Kode verifikasi baru berhasil dikirim ulang!');
    }

    // Tampilan input OTP
    public function showLoginOtp()
    {
        if (!session()->has('login_user_id')) {
            return redirect()->route('login');
        }

        // Kalau udah lebih dari 5 menit, gw suru balik mampus
        if (time() - session('login_otp_time') > 300) {
            session()->forget(['login_user_id', 'login_otp', 'login_otp_time']);
            return redirect()->route('login')->withErrors(['email' => 'Sesi OTP telah kadaluarsa (5 Menit). Silakan login kembali.']);
        }

        return Inertia::render('Auth/LoginOtp', [
            'otpTime' => session('login_otp_time')
        ]);
    }

    // Cek bener gak kodenya
    public function verifyLoginOtp(Request $request)
    {
        $request->validate(['otp' => 'required|numeric']);

        if (time() - session('login_otp_time') > 300) {
            session()->forget(['login_user_id', 'login_otp', 'login_otp_time']);
            return back()->withErrors(['otp' => 'Kode OTP kadaluarsa (lebih 5 menit). Minta ulang kodenya.']);
        }

        if ($request->otp == session('login_otp')) {
            $user = User::find(session('login_user_id'));
            Auth::login($user);
            
            // Bersihin session kalo sukses
            session()->forget(['login_user_id', 'login_otp', 'login_otp_time']);
            $request->session()->regenerate();
            return redirect()->intended('/admin');
        }

        return back()->withErrors(['otp' => 'Kode OTP salah, silakan cek kembali!']);
    }

    // Fungsi keluar
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}