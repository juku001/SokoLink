<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AcademyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/academy",
     *     tags={"Academy"},
     *     summary="List all academies with lessons",
     *     description="Retrieve all academies along with their associated lessons.",
     *     operationId="listAcademies",
     *     @OA\Response(
     *         response=200,
     *         description="List of academies retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Academy lesson list."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Web Development Bootcamp"),
     *                     @OA\Property(property="subtitle", type="string", example="Learn full-stack development in 12 weeks"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                     @OA\Property(
     *                         property="lessons",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="academy_id", type="integer", example=1),
     *                             @OA\Property(property="title", type="string", example="Introduction to HTML"),
     *                             @OA\Property(property="description", type="string", example="Learn the basics of HTML"),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T12:00:00Z")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $data = Academy::with('lessons')->get();
        return ResponseHelper::success($data, 'Academy lesson list.');
    }


    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/academy",
     *     tags={"Academy"},
     *     summary="Create a new academy",
     *     description="Allows creation of a new academy with a title and subtitle.",
     *     operationId="storeAcademy",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","subtitle"},
     *             @OA\Property(property="title", type="string", example="Web Development Bootcamp"),
     *             @OA\Property(property="subtitle", type="string", example="Learn full-stack development in 12 weeks")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Academy added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Academy added successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Web Development Bootcamp"),
     *                 @OA\Property(property="subtitle", type="string", example="Learn full-stack development in 12 weeks"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to validate fields"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="title",
     *                     type="array",
     *                     @OA\Items(type="string", example="The title field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="subtitle",
     *                     type="array",
     *                     @OA\Items(type="string", example="The subtitle field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'subtitle' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields', 422);
        }

        $academy = Academy::create($request->all());

        return ResponseHelper::success(
            $academy,
            'Academy added successful',
            201
        );
    }


    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/academy/{id}",
     *     tags={"Academy"},
     *     summary="Get details of a specific academy",
     *     description="Retrieve details of a single academy along with its lessons.",
     *     operationId="getAcademy",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the academy to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Academy details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Academy details"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Web Development Bootcamp"),
     *                 @OA\Property(property="subtitle", type="string", example="Learn full-stack development in 12 weeks"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                 @OA\Property(
     *                     property="lessons",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="academy_id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Introduction to HTML"),
     *                         @OA\Property(property="description", type="string", example="Learn the basics of HTML"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T12:00:00Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Academy not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Academy not found."),
     *           
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        $academy = Academy::with('lessons')->find($id);

        if (!$academy) {
            return ResponseHelper::error([], "Academy not found.", 404);
        }

        return ResponseHelper::success($academy, 'Academy details');
    }


    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/academy/{id}",
     *     tags={"Academy"},
     *     summary="Update an existing academy",
     *     description="Update the title and subtitle of a specific academy.",
     *     operationId="updateAcademy",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the academy to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Advanced Web Development Bootcamp"),
     *             @OA\Property(property="subtitle", type="string", example="Master full-stack development in 12 weeks")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Academy updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Academy updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Advanced Web Development Bootcamp"),
     *                 @OA\Property(property="subtitle", type="string", example="Master full-stack development in 12 weeks"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-14T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-14T12:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Academy not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Academy not found"),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $academy = Academy::find($id);

        if (!$academy) {
            return ResponseHelper::error([], 'Academy not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields', 422);
        }

        try {
            $academy->update($validator->validated());

            return ResponseHelper::success(
                $academy,
                'Academy updated successfully',
                200
            );
        } catch (\Exception $e) {
            return ResponseHelper::error([], 'Error: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/academy/{id}",
     *     tags={"Academy"},
     *     summary="Delete an academy",
     *     description="Delete a specific academy and all its associated lessons.",
     *     operationId="deleteAcademy",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the academy to delete",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Academy deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Academy deleted successful."),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Academy not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Academy not found."),
     *             
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $academy = Academy::with('lessons')->find($id);

        if (!$academy) {
            return ResponseHelper::error([], "Academy not found.", 404);
        }

        $academy->delete();
        return ResponseHelper::success([], "Academy deleted successful.");
    }

}
