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
    public function index()
    {
        $data = Academy::with('lessons')->get();
        return ResponseHelper::success($data, 'Academy lesson list.');
    }

    /**
     * Store a newly created resource in storage.
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
