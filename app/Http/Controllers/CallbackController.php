<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CallbackController extends Controller
{
    public function airtel(){
        return response()->json([
            'status'=> true,
            'message'=> 'Airtel callback route is set.'
        ]);
    }
}
