<?php

namespace App\Http\Controllers;

use App\Models\AcademyLesson;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Str;

class AcademyLessonController extends Controller
{
    /**
     * Store a new lesson for an academy
     */
    /**
     * @OA\Post(
     *     path="/academy/{id}/lessons",
     *     tags={"Academy"},
     *     summary="Add a lesson to an academy",
     *     description="Create a new lesson under a specific academy",
     *     operationId="addAcademyLesson",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the academy",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Introduction to Laravel"),
     *             @OA\Property(property="subtitle", type="string", example="Getting started with Laravel basics"),
     *             @OA\Property(property="instructor", type="string", example="John Doe"),
     *             @OA\Property(property="video_location", type="string", enum={"online","stored"}, example="online"),
     *             @OA\Property(property="video", type="string", example="https://youtube.com/example")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lesson added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson added successfully"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Academy not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Academy not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         ref="#/components/responses/422"
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Forbidden",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function store(Request $request, string $id)
    {
        $academy = Academy::find($id);
        if (!$academy) {
            return ResponseHelper::error([], "Academy not found", 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string|max:255',
            'instructor' => 'nullable|string|max:255',
            'video_location' => 'required|in:online,stored',
            'video' => 'required', // can be file or link
        ], [
            'video_location.in' => 'Location can be online or stored'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation failed', 422);
        }

        $data = $validator->validated();
        $videoPath = null;

        if ($data['video_location'] === 'stored' && $request->hasFile('video')) {
            $videoPath = $request->file('video')->store('academy/videos', 'public');
        } elseif ($data['video_location'] === 'online') {
            $videoPath = $data['video'];
        }

        $lesson = AcademyLesson::create([
            'academy_id' => $academy->id,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'instructor' => $data['instructor'] ?? null,
            'video_path' => $videoPath,
            'video_location' => $data['video_location'],
        ]);

        return ResponseHelper::success($lesson, "Lesson added successfully", 201);
    }



    /**
     * @OA\Get(
     *     path="/academy/lessons/{id}",
     *     tags={"Academy"},
     *     summary="Get a single lesson by ID",
     *     description="Retrieve details of a specific lesson in the academy",
     *     operationId="getAcademyLesson",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the lesson",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson details"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lesson not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        $lesson = AcademyLesson::find($id);

        if (!$lesson) {
            return ResponseHelper::error([], "Lesson not found.", 404);
        }

        return ResponseHelper::success($lesson, "Lesson details");
    }





    /**
     * Update an existing lesson
     */
    /**
     * @OA\Put(
     *     path="/academy/lessons/{id}",
     *     tags={"Academy"},
     *     summary="Update a lesson by ID",
     *     description="Update details of an existing lesson in the academy",
     *     operationId="updateAcademyLesson",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the lesson to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Lesson Title"),
     *             @OA\Property(property="subtitle", type="string", example="Updated Subtitle"),
     *             @OA\Property(property="instructor", type="string", example="Jane Doe"),
     *             @OA\Property(property="video_location", type="string", enum={"online","stored"}, example="online"),
     *             @OA\Property(property="video", type="string", example="https://youtube.com/...") 
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson updated successfully"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lesson not found"),
     *             @OA\Property(property="code", type="integer", example=404),
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
        $lesson = AcademyLesson::find($id);
        if (!$lesson) {
            return ResponseHelper::error([], "Lesson not found", 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'sometimes|string|max:255',
            'instructor' => 'nullable|string|max:255',
            'video_location' => 'sometimes|in:online,stored',
            'video' => 'sometimes',
        ], [
            'video_location.in' => 'Location can be online or stored'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation failed', 422);
        }

        $data = $validator->validated();

        if (isset($data['video_location'])) {
            if ($data['video_location'] === 'stored' && $request->hasFile('video')) {
                $data['video_path'] = $request->file('video')->store('academy/videos', 'public');
            } elseif ($data['video_location'] === 'online' && isset($data['video'])) {
                $data['video_path'] = $data['video'];
            }
            unset($data['video']);
        }

        $lesson->update($data);

        return ResponseHelper::success($lesson, "Lesson updated successfully");
    }




    /**
     * @OA\Delete(
     *     path="/academy/lessons/{id}",
     *     tags={"Academy"},
     *     summary="Delete a lesson by ID",
     *     description="Deletes a lesson from the academy",
     *     operationId="deleteAcademyLesson",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the lesson to delete",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson successfully deleted"),
     *             @OA\Property(property="code", type="integer", example=200),             
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lesson not found"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             
     *         )
     *     ),
     *      @OA\Response(
     *       response=401,
     *       description="Unauthroized",
     *       ref="#/components/responses/401"
     *     ),
     *     @OA\Response(
     *       response=403,
     *       description="Forbidden",
     *       ref="#/components/responses/403"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $lesson = AcademyLesson::find($id);

        if (!$lesson) {
            return ResponseHelper::error([], "Lesson not found.", 404);
        }

        $lesson->delete();
        return ResponseHelper::success([], "Lesson successfully deleted.");
    }

}
