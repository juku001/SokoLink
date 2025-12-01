<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\SuccessStory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SuccessStoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/success-stories",
     *     tags={"Success Stories"},
     *     summary="Get list of success stories",
     *     description="Returns all the success stories allowed by the super admin , with related buyer, category, and store.",
     *     @OA\Response(
     *         response=200,
     *         description="List of all the shown success stories",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of success stories."),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="category", type="string", example="Chakula na Vinywaji"),
     *                     @OA\Property(property="content", type="string", example="I bought this product and it changed my business."),
     *                     @OA\Property(property="store", type="string", example="Ulonda's Kitchen"),
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $storiesList = SuccessStory::with(['buyer', 'category', 'store'])->where('is_shown', true)->latest()->get();

        $stories = $storiesList->map(function ($story) {
            return [
                'id' => $story->id,
                'name' => $story->name,
                'content' => $story->content,
                'category' => $story->category->name,
                'store' => optional($story->store)->name
            ];
        });


        return ResponseHelper::success($stories, 'List of shown success stories');
    }

    /**
     * @OA\Post(
     *     path="/success-stories",
     *     tags={"Success Stories"},
     *     security={{"bearerAuth": {}}},
     *     summary="Create a new success story",
     *     description="Creates a new success story for a buyer. Has to be logged in.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "content","category_id"},
     *             @OA\Property(property="name", type="string", example="My First Success"),
     *             @OA\Property(property="category_id", type="integer", example=2),
     *             @OA\Property(property="store_id", type="integer", nullable=true ,  example=3),
     *             @OA\Property(property="content", type="string", example="I bought this product and it changed my business.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Success story created",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success story created successfully!"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="buyer_id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="My First Success"),
     *                 @OA\Property(property="category_id", type="integer", example=2),
     *                 @OA\Property(property="store_id", type="integer", example=3),
     *                 @OA\Property(property="content", type="string", example="I bought this product and it changed my business."),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-07T10:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/422"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'store_id' => 'nullable|exists:stores,id',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields', 422);
        }


        $validated = $validator->validated();

        $validated['buyer_id'] = auth()->user()->id;


        $story = SuccessStory::create($validated);

        return ResponseHelper::success($story, 'Success story created successful.', 201);
    }




    /**
     * @OA\Get(
     *     path="/success-stories/all",
     *     tags={"Success Stories"},
     *     summary="Get all success stories",
     *    security={{"bearerAuth": {}}},
     *     description="Fetches a list of all success stories including buyer, category, and store relationships. This is for the Super Admin",
     *     @OA\Response(
     *         response=200,
     *         description="List of all success stories",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List of all success stories"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="buyer_id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="JuKu"),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(property="store_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="content", type="string", example="Here is my view on Soko Link. I really enjoyed it. the very first time i visited it was amazing."),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-07T10:55:17.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-07T10:55:17.000000Z"),
     *                     @OA\Property(property="is_shown", type="boolean", example=true),
     *                     
     *                     @OA\Property(
     *                         property="buyer",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", nullable=true, example=null),
     *                         @OA\Property(property="phone", type="string", example="+255714257454"),
     *                         @OA\Property(property="email", type="string", nullable=true, example=null),
     *                         @OA\Property(property="role", type="string", example="buyer"),
     *                         @OA\Property(property="status", type="string", example="active"),
     *                         @OA\Property(property="last_login_at", type="string", example="2025-10-07 10:48:25"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-07T10:47:28.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-07T10:48:25.000000Z")
     *                     ),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                         @OA\Property(property="name", type="string", example="Chakula na Vinywaji"),
     *                         @OA\Property(property="slug", type="string", example="chakula-na-vinywaji"),
     *                         @OA\Property(property="icon", type="string", example="🍽️"),
     *                         @OA\Property(property="is_active", type="boolean", example=true),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-07T09:39:34.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-07T09:39:34.000000Z")
     *                     ),
     *                     @OA\Property(property="store", type="object", nullable=true, example=null)
     *                 )
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       ref="#/components/responses/401"
     *     ),
     *      @OA\Response(
     *       response=403,
     *       description="Forbidden",
     *       ref="#/components/responses/403"
     *     )
     * )
     */


    public function all()
    {
        $stories = SuccessStory::with(['buyer', 'category', 'store'])->latest()->get();

        return ResponseHelper::success($stories, 'List of all success stories');
    }



    /**
     * @OA\Put(
     *     path="/success-stories/{id}",
     *     tags={"Success Stories"},
     *     security={{"bearerAuth": {}}},
     *     summary="Toggle the shown status of a success story",
     *     description="Toggles the is_shown field of a success story between true and false.This is for the Super Admin",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the success story to toggle",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Story shown status changed.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Story not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Story not found.")
     *         )
     *     ),
     *     @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       ref="#/components/responses/401"
     *     ),
     *      @OA\Response(
     *       response=403,
     *       description="Forbidden",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $story = SuccessStory::find($id);

        if (!$story) {
            return ResponseHelper::error([], 'Story not found.', 404);
        }

        $shown = $story->is_shown;
        $story->is_shown = !$shown;
        $story->save();

        return ResponseHelper::success([], 'Story shown status changed.');
    }

}
