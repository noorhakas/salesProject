<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompanyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // هات الـ id بس من الموديل، مش الموديل كامل
        $companyId = $this->company?->id;

        return match(request()->method()){
            "POST" => [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('companies', 'name')->whereNull('deleted_at'),
                ],
            ],
            "PUT", "PATCH" => [
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('companies', 'name')
                        ->ignore($companyId)
                        ->whereNull('deleted_at'),
                ],
            ],
        };
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(response()->json(
            ['status' => false, 'errors' => $errors],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY
        ));
    }
}