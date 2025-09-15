<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Order;
use App\Models\Shipment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{


    public function index()
    {
        $authId = auth()->user()->id;
        $shipment = Shipment::with('order')->where("seller_id", $authId)->get();
        return ResponseHelper::success($shipment, 'List of shipment order for the sellers.');
    }






    public function update(Request $request, string $id)
    {

        $authId = auth()->user()->id;
        $shipment = Shipment::where('id', $id)->where('seller_id', $authId)->first();
        if (!$shipment) {
            return ResponseHelper::error([], 'Shipment not found');
        }
        $validator = Validator::make($request->all(), [
            'carrier' => 'required|string',
            'carrier_mobile'=>'required|string|regex:/^\+255\d{9}$/'
        ],[
            'carrier_mobile.regex' => 'Mobile number should be +255XXXXXXXXX'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                'Failed to validate fields',
                422
            );
        }

        DB::beginTransaction();
        try {
            $order = Order::find($shipment->order_id);

            if ($order->status === 'pending') {
                return ResponseHelper::error([], 'Order is not yet paid for.', 400);
            }
            if (in_array($order->status, ['shipped', 'delivered'])) {
                return ResponseHelper::error([], 'This order cannot be shipped again.', 400);
            }

            $order->status = 'shipped';
            $order->save();

            $shipment->carrier = $request->carrier;
            $shipment->carrier_mobile = $request->carrier_mobile;
            $shipment->status = 'shipped';
            $shipment->shipped_at = now();
            $shipment->save();

            DB::commit();
            return ResponseHelper::success([], 'Products shipped to buyer.');

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }


    public function delivered(Request $request)
    {

    }



}
