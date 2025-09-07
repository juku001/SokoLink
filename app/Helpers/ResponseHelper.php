<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Success response.
     */
    public static function success($data = [], $message = 'Request successful', $statusCode = 200)
    {
        $response = [
            'status' => true,
            'message' => $message,
            'code' => $statusCode,
        ];
        if (!empty($data)) {
            $response['data'] = $data;
        }
        return response()->json($response, $statusCode);
    }

    /**
     * Error response.
     */
    public static function error($errors = [], $message = 'Something went wrong', $statusCode = 500)
    {
        $response = [
            'status' => false,
            'message' => $message,
            'code' => $statusCode
        ];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        return response()->json($response, $statusCode);
    }
}