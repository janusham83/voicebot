<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Build a consistent successful JSON response.
     */
    protected function success(string $message = '', array $data = [], int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Build a consistent error JSON response.
     */
    protected function error(string $message = '', int $status = 400, array $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
