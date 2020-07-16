<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseController extends Controller
{
    protected $request;
    protected $service;

    /**
     * BaseController constructor.
     */

    public function __construct()
    {
        $this->request = request();
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        return $this->indexDefault($request);
    }

    /**
     * @param Request $request
     * @param array $relations
     * @param bool $withTrashed
     * @return JsonResponse
     */

    public function indexDefault(Request $request, array $relations = [], $withTrashed = false)
    {
        try {
            $request = $request ?: $this->request;
            $params = $request->only(['page', 'limit', 'filter', 'sort']);
            $data = $this->service->buildBasicQuery($params, $relations, $withTrashed);
            return $this->successResponse($data);
        } catch (Exception $e) {
            return $this->errorResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        return $this->showDefault($id);
    }

    /**
     * @param $id
     * @param array $relations
     * @param array $appends
     * @param array $hiddens
     * @param bool $withTrashed
     * @return JsonResponse
     */

    public function showDefault($id, array $relations = [], array $appends = [], array $hiddens = [], $withTrashed = false)
    {
        try {
            $data = $this->service->show($id, $relations, $appends, $hiddens, $withTrashed);
            return $this->successResponse($data);
        } catch (Exception $e) {
            return $this->errorResponse();
        }
    }
}
