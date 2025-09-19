<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    /**
     * Store a new feedback.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */


    /**
     * @OA\Post(
     *     path="/feedbacks",
     *     tags={"Feedbacks"},
     *     summary="Submit new feedback",
     *     description="Stores user feedback and sends an email notification to the admin.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="feedback", type="string", example="Great service!"),
     *             @OA\Property(property="rating", type="integer", format="int32", example=5),
     *             @OA\Property(property="image", type="string", format="binary", description="Optional image upload")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Feedback submitted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Feedback submitted successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="feedback", type="string", example="Great service!"),
     *                 @OA\Property(property="rating", type="integer", example=5),
     *                 @OA\Property(property="image", type="string", example="feedback_images/example.jpg"),
     *                 @OA\Property(property="created_at", type="string", example="2025-09-11T10:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-09-11T10:00:00.000000Z")
     *             )
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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feedback' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), 'Failed to validate fields.', 422);
        }

        $validated = $validator->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('feedback_images', 'public');
            $validated['image'] = $path;
        }

        $validated['user_id'] = $request->user()->id ?? null;

        $feedback = Feedback::create($validated);

        Mail::raw(
            "New feedback received:\n\nRating: {$feedback->rating}\nFeedback: {$feedback->feedback}\nUser ID: {$feedback->user_id}",
            function ($message) {
                $message->to('ides@sokolink.store')
                    ->subject('New Feedback Received');
            }
        );

        return ResponseHelper::success($feedback, 'Feedback submitted successfully.');
    }



    /**
     * @OA\Get(
     *     path="/feedbacks",
     *     tags={"Feedbacks"},
     *     summary="List all feedbacks",
     *     description="Retrieves a paginated list of customer feedbacks, ordered by most recent.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Feedbacks retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="List of customer feedbacks"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="user_id", type="integer", example=2),
     *                         @OA\Property(property="feedback", type="string", example="Great service!"),
     *                         @OA\Property(property="rating", type="integer", example=5),
     *                         @OA\Property(property="image", type="string", example="feedback_images/example.jpg"),
     *                         @OA\Property(property="created_at", type="string", example="2025-09-11T10:00:00.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", example="2025-09-11T10:00:00.000000Z")
     *                     )
     *                 ),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=50),
     *                 @OA\Property(property="total", type="integer", example=250)
     *             )
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

    public function index()
    {

        $feedbacks = Feedback::orderBy('created_at', 'desc')->paginate(50);

        return ResponseHelper::success($feedbacks, "List of customer feedbacks");
    }
}
