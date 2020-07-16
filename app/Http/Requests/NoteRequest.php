<?php

namespace App\Http\Requests;

class NoteRequest extends BaseRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:50'
        ];
    }
}
