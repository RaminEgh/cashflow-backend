<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class Helper
{
    /**
     * Return a success response.
     *
     * @param string $message
     * @param array $data
     * @param int $status
     * @return JsonResponse
     */
    public static function successResponse($message, $data = [], $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param array $data
     * @param int $status
     * @return JsonResponse
     */
    public static function errorResponse($message, $data = [], $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $status);
    }


    public static function persianToLatin($string): string
    {
        $map = [
            'ا' => 'a', 'آ' => 'a', 'ب' => 'b', 'پ' => 'p', 'ت' => 't', 'ث' => 's', 'ج' => 'j',
            'چ' => 'ch', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'z', 'ر' => 'r', 'ز' => 'z',
            'ژ' => 'zh', 'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'z', 'ط' => 't', 'ظ' => 'z',
            'ع' => 'a', 'غ' => 'gh', 'ف' => 'f', 'ق' => 'gh', 'ک' => 'k', 'گ' => 'g', 'ل' => 'l',
            'م' => 'm', 'ن' => 'n', 'و' => 'v', 'ه' => 'h', 'ی' => 'y', 'ء' => '', 'ٔ' => '', '‌' => ' ',
        ];

        return strtr($string, $map);
    }
}
