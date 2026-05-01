<?php

namespace Teksite\Module\Foundations;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Teksite\Lareon\Enums\ResponseType;

class ApiFormRequest extends FormRequest
{

    public function failedValidation(Validator $validator)
    {
        $exception = $validator->getException();
        $exc=(new $exception($validator))->errorBag($this->errorBag);

        return throw new HttpResponseException(response()->json([
            'message' => $exc->getMessage(),
            'errors' => $exc->errors(),
            'status' => 422,
            'data' => [],
        ])->setStatusCode(422));

    }


    public function failedAuthorization()
    {
        return throw new HttpResponseException(response()->json([
            'messages' => trans("Forbidden You don't have permission"),
            'errors' => ['auth'=>"Forbidden You don't have permission"],
            'status' => 403,
            'data' => [],
        ])->setStatusCode(403));
    }

}
