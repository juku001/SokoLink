<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            'auth:sanctum'
        ];
    }


    /**
     * @OA\Get(
     *     path="/carts",
     *     tags={"Cart"},
     *     summary="Get cart summary",
     *     description="Returns the authenticated buyer's cart with items, subtotal, shipping and total.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart summary",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="cart_id", type="integer", example=1),
     *                 @OA\Property(property="subtotal", type="number", format="float"),
     *                 @OA\Property(property="total", type="number", format="float"),
     *                 @OA\Property(property="shipping", type="number", format="float"),
     *                 @OA\Property(property="products", type="array",
     *                     @OA\Items(
     *                       @OA\Property(property="cart_item_id", type="number"),
     *                       @OA\Property(property="product_id", type="number"),
     *                         @OA\Property(property="product_name", type="string"),
     *                         @OA\Property(property="store_name", type="string"),
     *                         @OA\Property(property="price", type="number", format="float"),
     *                         @OA\Property(property="quantity", type="integer")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $authId = auth()->id();

        // Load cart with items + product + store
        $cart = Cart::with(['items.product.store'])
            ->where('buyer_id', $authId)
            ->first();

        if (!$cart) {
            return ResponseHelper::success([
                'subtotal' => 0,
                'total' => 0,
                'shipping' => 0,
                'products' => []
            ], "Cart summary");
        }

        // Transform items
        $products = $cart->items->map(function ($item) {
            return [
                'cart_item_id'=> $item->id,
                'product_id'=> $item->product->id,
                'product_name' => $item->product->name,
                'store_name' => $item->product->store->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
            ];
        });

        // Calculate subtotal
        $subTotal = $cart->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $shipping = 0; // flat rate for now

        $data = [
            'cart_id' => $cart->id,
            'subtotal' => $subTotal,
            'total' => $subTotal + $shipping,
            'shipping' => $shipping,
            'products' => $products
        ];

        return ResponseHelper::success($data, "Cart summary");
    }


    /**
     * @OA\Post(
     *     path="/carts",
     *     tags={"Cart"},
     *     summary="Add product to cart",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="integer", example=12),
     *             @OA\Property(property="quantity", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product added to cart"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|numeric|exists:products,id',
            'quantity' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Failed to validate fields", 422);
        }

        DB::beginTransaction();
        try {
            $authId = auth()->id();
            $product = Product::find($request->product_id);

            $deductQty = (int) $request->quantity;
            if ($product->stock_qty <= 0) {
                return ResponseHelper::error([], "Product is already out of stock.", 400);
            }

            if ($deductQty > $product->stock_qty) {
                return ResponseHelper::error([], "Cannot add more than available stock.", 400);
            }

            $cart = Cart::firstOrCreate(
                ['buyer_id' => $authId],
                ['buyer_id' => $authId]
            );

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $request->product_id)
                ->first();

            if ($cartItem) {
                // Update quantity
                $cartItem->quantity += $request->quantity;
                $cartItem->save();
            } else {
                // Create new cart item
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $request->quantity
                ]);
            }

            DB::commit();
            return ResponseHelper::success([], 'Product added to cart', 201);
        } catch (QueryException $e) {
            return ResponseHelper::error([], "Error : " . $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error([], "Error: " . $e->getMessage(), 500);
        }
    }


    /**
     * @OA\Patch(
     *     path="/carts/{itemId}",
     *     tags={"Cart"},
     *     summary="Update quantity of a cart item",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="itemId",
     *         in="path",
     *         required=true,
     *         description="ID of the cart item",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cart item updated"),
     *     @OA\Response(response=404, description="Cart item not found")
     * )
     */


    public function update(Request $request, string $itemId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(
                $validator->errors(),
                "Failed to validate fields",
                422
            );
        }

        $authId = auth()->id();

        // Find the cart item and make sure it belongs to the user's cart
        $cartItem = CartItem::where('id', $itemId)
            ->whereHas('cart', function ($q) use ($authId) {
                $q->where('buyer_id', $authId);
            })
            ->first();

        if (!$cartItem) {
            return ResponseHelper::error([], "Cart item not found", 404);
        }


        $product = Product::find($cartItem->product_id);
        if ($product->stock_qty <= 0) {
            return ResponseHelper::error([], "Product is already out of stock.", 400);
        }

        if ($request->quantity > $product->stock_qty) {
            return ResponseHelper::error([], "Cannot update more than available stock.", 400);
        }
        // Update quantity
        $cartItem->update([
            'quantity' => $request->quantity
        ]);

        return ResponseHelper::success(
            [
                'product_name' => $cartItem->product->name,
                'store_name' => $cartItem->product->store->name,
                'price' => $cartItem->price,
                'quantity' => $cartItem->quantity,
            ],
            "Cart item updated"
        );
    }


    /**
     * @OA\Post(
     *     path="/carts/{cartId}/add/{productId}/increment",
     *     tags={"Cart"},
     *     summary="Increment quantity of a product in the cart",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="cartId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product quantity incremented"),
     *     @OA\Response(response=404, description="Cart or product not found")
     * )
     */
    public function add(Request $request, $cartId, $productId)
    {
        $authId = auth()->id();

        // Check if the cart belongs to the authenticated user
        $cart = Cart::where('id', $cartId)
            ->where('buyer_id', $authId)
            ->first();

        if (!$cart) {
            return ResponseHelper::error([], "Cart not found", 404);
        }

        // Find the cart item
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if (!$cartItem) {
            return ResponseHelper::error([], "Product not found in cart", 404);
        }

        $product = Product::find($productId);

        if ($product->stock_qty <= 0) {
            return ResponseHelper::error([], "Product is already out of stock.", 400);
        }

        if (($cartItem->quantity + 1) > $product->stock_qty) {
            return ResponseHelper::error([], "Cannot add more than available stock.", 400);
        }
        $cartItem->quantity += 1;
        $cartItem->save();

        return ResponseHelper::success(
            [
                'product_name' => $cartItem->product->name,
                'store_name' => $cartItem->product->store->name,
                'price' => $cartItem->price,
                'quantity' => $cartItem->quantity,
            ],
            "Product quantity decremented"
        );
    }


    /**
     * @OA\Delete(
     *     path="/carts/{cartId}/remove/{productId}/decrement",
     *     tags={"Cart"},
     *     summary="Decrement quantity or remove product from cart",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="cartId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product quantity decremented or removed"),
     *     @OA\Response(response=404, description="Cart or product not found")
     * )
     */
    public function remove(Request $request, $cartId, $productId)
    {
        $authId = auth()->id();

        // Check if the cart belongs to the authenticated user
        $cart = Cart::where('id', $cartId)
            ->where('buyer_id', $authId)
            ->first();

        if (!$cart) {
            return ResponseHelper::error([], "Cart not found", 404);
        }

        // Find the cart item
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if (!$cartItem) {
            return ResponseHelper::error([], "Product not found in cart", 404);
        }

        // Decrement quantity or remove item if it reaches 0
        if ($cartItem->quantity > 1) {
            $cartItem->quantity -= 1;
            $cartItem->save();

            return ResponseHelper::success(
                [
                    'product_name' => $cartItem->product->name,
                    'store_name' => $cartItem->product->store->name,
                    'price' => $cartItem->price,
                    'quantity' => $cartItem->quantity,
                ],
                "Product quantity decremented"
            );
        } else {
            $cartItem->delete();

            return ResponseHelper::success(
                [],
                "Product removed from cart"
            );
        }
    }



}
