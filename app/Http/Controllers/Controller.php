<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param array $responseData
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($responseData = ['message' => 'OK'])
    {
        return response()->json($responseData);
    }

    /**
     * @param array $errorData
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse($errorData = null)
    {
        $errorData = $errorData ? $errorData : ['errors' => __('message.common.server_error')];
        return response()->json($errorData, isset($errorData['code']) ? $errorData['code'] : 500);
    }
}
