<?php

namespace App\Modules\IdentityAccess\Presentation\Http\Support;

use Illuminate\Http\JsonResponse;

final class IdentityApiResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function success(array $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'correlation_id' => self::correlationId(),
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function error(string $code, string $message, int $status, array $fields = []): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($fields !== []) {
            $error['champs'] = $fields;
        }

        return response()->json([
            'error' => $error,
            'correlation_id' => self::correlationId(),
        ], $status);
    }

    public static function empty(int $status = 204): JsonResponse
    {
        return response()->json(null, $status);
    }

    public static function correlationId(): string
    {
        $request = request();
        $id = $request->attributes->get('correlation_id');

        return is_string($id) && $id !== '' ? $id : (string) $request->headers->get('X-Correlation-Id', '');
    }
}
