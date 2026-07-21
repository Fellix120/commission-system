<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class SystemController extends Controller
{
    public function fresh()
    {
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        return redirect()->route('dashboard')->with('status', 'All tables were cleared and rebuilt with migrate:fresh --seed.');
    }
}
