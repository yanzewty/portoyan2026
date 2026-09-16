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

    
    // Misalnya buat nampilin flash message sukses/error dari controller
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            // masukin data tambahan di bawah sini kalo butuh
        ];
    }
}