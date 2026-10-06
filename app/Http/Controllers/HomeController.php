<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'appName' => 'LibraryHub',
            'description' => 'Tra cứu sách và quản lý hoạt động mượn trả.',
        ]);
    }
}
