<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class BaseController extends Controller
{
    protected $request;
    protected $service;

    public function __construct()
    {
        $this->request = request();
        $this->setService();
    }

    abstract protected function setService();

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return $this->indexDefault($request);
    }

    public function indexDefault(Request $request, array $relations = [], $withTrashed = false)
    {
        try {
            $request = $request ?: $this->request;
            $params = $request->only(['page', 'limit', 'filter', 'sort']);
            $data = $this->service->buildBasicQuery($params, $relations, $withTrashed);
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        return $this->showDefault($id);
    }

    public function showDefault($id, array $relations = [], array $appends = [], array $hiddens = [], $withTrashed = false)
    {
        try {
            $data = $this->service->show($id, $relations, $appends, $hiddens, $withTrashed);
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse();
        }
    }
}
