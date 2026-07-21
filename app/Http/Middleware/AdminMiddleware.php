<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{

    // public function handle(Request $request, Closure $next)
    // {
    //     if(Auth::check()){

    //         if(Auth::user()->role_as == '1'){
    //             return $next($request);
    //         }

    //         else {
    //             return redirect("/")->with('status', 'Access Denied! as you are not as admin');
    //         }
    //     } else {
    //         return redirect('/')->with('status', 'Please login Firsh');
    //     }
    // }

    // public function handle(Request $request, Closure $next, $role)
    // {
    //     // User not logged in
    //     if (!Auth::check()) {
    //         return redirect('/login')->with('status', 'Please login first');
    //     }

    //     // User role does NOT match required role
    //     if (Auth::user()->role_as != $role) {
    //         return redirect('/unauthorized')->with('status', 'Access Denied');
    //     }

    //     // Allowed → continue
    //     return $next($request);
    // }
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // User not logged in
        if (!Auth::check()) {
            return redirect('/login')->with('status', 'Please login first');
        }

        // User role does NOT match any required role
        if (!in_array((string) Auth::user()->role_as, $roles, true)) {
            return redirect('/unauthorized')->with('status', 'Access Denied');
        }

        // Allowed → continue
        return $next($request);
    }
}
