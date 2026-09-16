<?php

use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

// RUTE PUBLIK
Route::get('/', [PortfolioController::class, 'index'])->name('portofolio.index');
Route::post('/contact/send', [PortfolioController::class, 'storeMessage'])
    ->name('contact.send')
    ->middleware('throttle:3,5'); 

// RUTE LOGIN & OTP
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');

/* alasan ini untuk membatasi 3x salah password dalam 3 menit */
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:3,3');

/* alasan ini untuk membatasi kirim email OTP pertama agar server email tidak error karena spam */
Route::post('/login/request-otp', [LoginController::class, 'requestOtp'])
    ->name('login.request-otp')
    ->middleware('throttle:3,3');

Route::get('/login/otp', [LoginController::class, 'showLoginOtp'])->name('login.otp');

/*  untuk membatasi tebak angka salah maksimal 3x dalam 3 menit */
Route::post('/login/otp', [LoginController::class, 'verifyLoginOtp'])
    ->name('login.otp.verify')
    ->middleware('throttle:3,3');

/* alasan ini untuk memberi jeda pada tombol kirim ulang OTP (Resend) */
Route::post('/login/otp/resend', [LoginController::class, 'resendOtp'])
    ->name('login.otp.resend')
    ->middleware('throttle:3,3');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// RUTE ADMIN (Wajib Login)
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/', [PortfolioController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/home', [PortfolioController::class, 'editHome'])->name('admin.home');
    Route::post('/home/update', [PortfolioController::class, 'updateHome'])->name('admin.home.update');

    Route::get('/about', [PortfolioController::class, 'editAbout'])->name('admin.about');
    Route::post('/about', [PortfolioController::class, 'updateAbout'])->name('admin.about.update');

    Route::get('/latar-belakang-skill', [PortfolioController::class, 'keahlianAdmin'])->name('admin.latar_belakang');
    Route::post('/latar-belakang-skill', [PortfolioController::class, 'keahlianStore'])->name('admin.latar_belakang.store');
    Route::get('/latar-belakang-skill/{id}/edit', [PortfolioController::class, 'keahlianEdit'])->name('admin.latar_belakang.edit');
    Route::post('/latar-belakang-skill/{id}/update', [PortfolioController::class, 'keahlianUpdate'])->name('admin.latar_belakang.update');
    Route::delete('/latar-belakang-skill/{id}', [PortfolioController::class, 'keahlianDestroy'])->name('admin.latar_belakang.delete');
    Route::post('/latar-belakang-skill/header', [PortfolioController::class, 'updateSkillHeader'])->name('admin.latar_belakang.header');

    Route::get('/bidang-keahlian', [PortfolioController::class, 'bidangKeahlianAdmin'])->name('admin.keahlian');
    Route::post('/bidang-keahlian/update', [PortfolioController::class, 'updateBidangKeahlian'])->name('admin.bidang_keahlian.update');

    Route::get('/organizations', [PortfolioController::class, 'orgAdmin'])->name('admin.organizations');
    Route::post('/organizations', [PortfolioController::class, 'updateOrgAdmin'])->name('admin.organizations.update');

    Route::post('/panels', [PortfolioController::class, 'panelStore'])->name('admin.panels.store');
    Route::get('/panels/{id}/edit', [PortfolioController::class, 'panelEdit'])->name('admin.panels.edit');
    Route::put('/panels/{id}', [PortfolioController::class, 'panelUpdate'])->name('admin.panels.update');
    Route::delete('/panels/{id}', [PortfolioController::class, 'panelDestroy'])->name('admin.panels.delete');

    Route::get('/messages', [PortfolioController::class, 'messagesAdmin'])->name('admin.messages');
    Route::post('/messages/bulk-delete', [PortfolioController::class, 'bulkDeleteMessages'])->name('admin.messages.bulkDelete');
    Route::post('/messages/bulk-read', [PortfolioController::class, 'bulkReadMessages'])->name('admin.messages.bulkRead');
    Route::post('/messages/read-all', [PortfolioController::class, 'markAllAsRead'])->name('admin.messages.readAll');
    Route::delete('/messages/{id}', [PortfolioController::class, 'deleteMessage'])->name('admin.messages.delete');
    Route::post('/messages/{id}/read', [PortfolioController::class, 'markAsRead'])->name('admin.messages.read');
});