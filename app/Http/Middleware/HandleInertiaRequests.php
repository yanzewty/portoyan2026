<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    // Kerangka blade utama yang diload pas web pertama kali dibuka
    protected $rootView = 'app';

    // Buat ngecek versi file asset (css/js) biar nggak nyangkut di cache browser
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    // Data yang otomatis dikirim ke SEMUA halaman Vue (bisa dibaca lewat usePage().props / $page.props)
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            // Status login: dibaca Portfolio.vue lewat page.props.auth?.user
            // (kalau null = belum login, kalau ada isi = sudah login).
            // Sengaja cuma id & name, jangan kirim email/data sensitif ke halaman publik.
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                ] : null,
            ],

            // Flash message dari controller, contoh di controller: ->with('success_msg', 'Berhasil!')
            'flash' => [
                'success_msg' => fn () => $request->session()->get('success_msg'),
            ],
        ];
    }
}