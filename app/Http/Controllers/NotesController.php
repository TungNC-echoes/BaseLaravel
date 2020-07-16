<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\NoteRequest;
use Illuminate\Http\JsonResponse;
use App\Services\NoteService;
use Illuminate\Support\Facades\DB;

class NotesController extends BaseController
{
    /**
     * NotesController constructor.
     * @param NoteService $noteService
     */
    public function __construct(NoteService $noteService)
    {
        $this->service = $noteService;
        parent::__construct();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */

    public function index(Request $request)
    {
        return $this->indexDefault($request);
    }

    /**
     * @param NoteRequest $request
     * @return JsonResponse
     */

    public function store(NoteRequest $request)
    {
        try {
            DB::beginTransaction();
            $result = $this->service->store($request->all());
            DB::commit();
            return $this->successResponse($result);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse();
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */

    public function destroy($id)
    {
        try {
            $result = $this->service->destroy($id);
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse();
        }
    }
}
