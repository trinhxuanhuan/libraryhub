<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        return view('about', [
            'description' => 'LibraryHub hỗ trợ tra cứu sách và quản lý hoạt động thư viện.',
            'features' => [
                'Tra cứu thông tin sách.',
                'Quản lý sách, tác giả và thể loại.',
                'Theo dõi việc mượn, gia hạn và trả sách.',
            ],
        ]);
    }
}
