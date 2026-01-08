<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Return a successful JSON response
     */
    protected function success($data = null, string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = ['success' => true];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response
     */
    protected function error(string $message, string $errorCode = 'ERROR', int $statusCode = 400, array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error' => $errorCode,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error JSON response
     */
    protected function validationError(array $errors, string $message = 'Validation failed. Please check your input.'): JsonResponse
    {
        return $this->error($message, 'VALIDATION_ERROR', 422, $errors);
    }

    /**
     * Return a not found JSON response
     */
    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error($message, 'NOT_FOUND', 404);
    }

    /**
     * Return a forbidden JSON response
     */
    protected function forbidden(string $message = 'You do not have permission to perform this action.'): JsonResponse
    {
        return $this->error($message, 'FORBIDDEN', 403);
    }

    /**
     * Return an unauthorized JSON response
     */
    protected function unauthorized(string $message = 'Unauthenticated. Please provide a valid authentication token.'): JsonResponse
    {
        return $this->error($message, 'UNAUTHENTICATED', 401);
    }
}
