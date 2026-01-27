<?php

namespace App\Traits;

trait ResponseApi
{
    /**
     * Core of response
     *
     * @param   string          $message
     * @param   array|object    $data
     * @param   integer         $statusCode
     * @param   bool         $isSuccess
     */
    public function coreResponse($message, $statusCode, $data = null, $isSuccess = true)
    {
        // Check the params
        if (!$message) {
            return response()->json(['message' => 'Message is required'], 500);
        }

        // Send the response
        if ($isSuccess) {
            return response()->json([
                'message' => $message,
                'code' => $statusCode,
                'results' => $data
            ], $statusCode);
        } else {
            return response()->json([
                'message' => $message,
                'error' => true,
                'code' => $statusCode,
            ], $statusCode);
        }
    }
    /**
     * Send any success response
     *
     * @param   string          $message
     * @param   array|object    $data
     * @param   int         $statusCode
     */
    public function success($message, $data, $statusCode = 200)
    {
        return $this->coreResponse($message, $data, $statusCode);
    }

    /**
     * Send any error response
     *
     * @param   string          $message
     * @param   int         $statusCode
     */
    public function error($message, $statusCode = 500)
    {
        return $this->coreResponse($message, $statusCode, false);
    }
}