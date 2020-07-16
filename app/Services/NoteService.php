<?php

namespace App\Services;

use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use DB;

class NoteService extends BaseService
{
    /**
     * NoteService constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    protected function setModel()
    {
        $this->model = new Note();
    }
}
