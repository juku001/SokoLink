<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Store;
use App\Models\StoreFollow;
use Illuminate\Http\Request;

class StoreFollowingController extends Controller
{


    /**
     * @OA\Get(
     *     path="/stores/{id}/follows",
     *     summary="List buyers who follow a store",
     *     description="Returns buyers following the given store. Seller must own the store.",
     *     tags={"Stores"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Store ID owned by the authenticated seller",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of followings",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of followings"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=12),
     *                     @OA\Property(property="user_id", type="integer", example=34),
     *                     @OA\Property(property="phone", type="string", example="+255700000000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     )
     * )
     */

    public function index(Request $request, string $id)
    {
        $authId = auth()->user()->id;
        $store = Store::where('id', $id)->where('seller_id', $authId)->first();

        if (!$store) {
            return ResponseHelper::error([], 'Store not found', 404);
        }

        $following = StoreFollow::with('buyer')->where('store_id', $store->id)->get();

        $followingData = $following->map(function ($item) use ($authId) {
            return [
                'id' => $item->id,
                'user_id' => $item->buyer_id,
                'phone' => $item->buyer->phone
            ];
        });

        return ResponseHelper::success($followingData, 'List of followings');
    }






    /**
     * @OA\Patch(
     *     path="/stores/{id}/follows",
     *     summary="Follow or unfollow a store",
     *     description="Toggle the follow status for the authenticated buyer.",
     *     tags={"Stores"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Store ID to follow or unfollow",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Follow status updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Following status updated."),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="string", enum={"followed","unfollowed"}, example="unfollowed")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Store not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Store not found."),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         ref="#/components/responses/401"
     *     )
     * )
     */

    public function update(string $id)
    {
        $authId = auth()->id();

        $store = Store::find($id);
        if (!$store) {
            return ResponseHelper::error([], 'Store not found.', 404);
        }

        $follow = StoreFollow::where('store_id', $store->id)
            ->where('buyer_id', $authId)
            ->first();

        if ($follow) {
            $follow->delete();
            $status = 'unfollowed';
        } else {
            // Follow
            StoreFollow::create([
                'store_id' => $store->id,
                'buyer_id' => $authId,
            ]);
            $status = 'followed';
        }

        return ResponseHelper::success(
            ['status' => $status],
            'Following status updated.',
            200
        );
    }

}
