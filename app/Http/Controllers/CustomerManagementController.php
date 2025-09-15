<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerManagementController extends Controller
{


    public function index(Request $request)
    {
        $query = User::with([
            'payments' => function ($q) {
                $q->latest();
            }
        ])->where('role', 'buyer');


        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->get();

        $data = $customers->map(function ($customer) {
            $lastActive = $customer->payments->isNotEmpty()
                ? $customer->payments->first()->created_at
                : $customer->created_at;

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'status' => $customer->status,
                'last_active' => $lastActive,
                'purchases' => $customer->payments->count(),
            ];
        });

        return ResponseHelper::success($data, "List of customers");
    }





    public function show($id)
    {
        $user = User::with('payments.order')->find($id);
        if (!$user) {
            return ResponseHelper::error([], "User not found.", 404);
        }

        return ResponseHelper::success($user, 'Customer details');


    }
}
