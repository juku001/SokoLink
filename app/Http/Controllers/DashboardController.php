<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\AcademyLesson;
use App\Models\Contact;
use Illuminate\Http\Request;

class DashboardController extends Controller
{


    public function seller()
    {

    }


    public function contacts()
    {

        $id = auth()->user()->id;

        $stats = Contact::selectRaw("type, COUNT(*) as count")
            ->where('user_id', $id)
            ->groupBy('type')
            ->pluck('count', 'type');

        $data = [
            'total' => $stats->sum(),
            'clients' => $stats['client'] ?? 0,
            'customers' => $stats['customer'] ?? 0,
            'suppliers' => $stats['supplier'] ?? 0,
        ];

        return ResponseHelper::success($data, 'Contact Dashboard Stats');
    }

    public function academy()
    {


        $videos = AcademyLesson::count();
        $data = [
            'videos' => $videos ?? 0,
            'ratings' => $ratings ?? 0,
            'students' => $students ?? 0,
            'content' => [
                'state' => 'Free',
                'message' => 'All Content'
            ]
        ];
    }
}
