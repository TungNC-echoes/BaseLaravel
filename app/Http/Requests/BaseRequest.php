<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $error){
            $errors[$key] = $error[0];
        }
        throw new HttpResponseException(response()->json(['errors' => $errors
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }

    /**get
     * Keep only fields with rules
     * @return array
     */
    public function getData()
    {
        return $this->only(array_keys($this->rules()));
    }

    public function validationData()
    {
        return array_merge($this->all(), $this->route()->parameters());
    }

    public static function convertToArray($value)
    {
        if (is_array($value) && count($value) == 1) {
            $value = reset($value);
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        } elseif (!is_array($value)) {
            $value = array(null);
        }

        $value = array_map('trim', $value);

        return $value;
    }
}
