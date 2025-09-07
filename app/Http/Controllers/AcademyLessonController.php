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
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Validation failed', 422);
        }

        $data = $validator->validated();
        $videoPath = null;

        // Handle video based on location
        if ($data['video_location'] === 'stored' && $request->hasFile('video')) {
            $videoPath = $request->file('video')->store('academy/videos', 'public');
        } elseif ($data['video_location'] === 'online') {
            $videoPath = $data['video']; // YouTube/Vimeo/etc. link
        }

        $lesson = AcademyLesson::create([
            'category_id' => $academy->id,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'instructor' => $data['instructor'] ?? null,
            'video_path' => $videoPath,
            'video_location' => $data['video_location'],
        ]);

        return ResponseHelper::success($lesson, "Lesson added successfully", 201);
    }



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
            unset($data['video']); // remove temp field
        }

        $lesson->update($data);

        return ResponseHelper::success($lesson, "Lesson updated successfully");
    }



    public function destroy(string $id)
    {
        $lesson = AcademyLesson::find($id);

        if (!$lesson) {
            return ResponseHelper::error([], "Lesson not found.", 404);
        }

        $lesson->delete();
        return ResponseHelper::success([], "lesson successful deleted.");
    }
}
