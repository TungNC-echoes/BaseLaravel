<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function setModel()
    {
        $this->model = new User();
    }

    public function activate($id)
    {
        return $this->update($id, ['status' => User::ACTIVATE]);
    }

    public function deactivate($id)
    {
        return $this->update($id, ['status' => User::DEACTIVATE]);
    }

    public function addFilter()
    {
        $this->query->where('id', '!=', Auth::user()->id);
        /*$this->query->whereDoesntHave('roles', function ($builder) {
            $builder->where('name', 'LIKE', '%admin%');
        });*/
    }
}
