<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BranchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // هات الـ id بس من الموديل، مش الموديل كامل
        $branchId = $this->branch?->id;

        return match (request()->method()) {
            "POST" => [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('branches', 'name')->whereNull('deleted_at'),
                ],
                'address'  => 'required|string',
                'phone'    => 'nullable|string|regex:/^[0-9]{10,15}$/',
                'whatsapp' => 'nullable|string|regex:/^[0-9]{10,15}$/',
            ],
            "PUT", "PATCH" => [
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('branches', 'name')
                        ->ignore($branchId)
                        ->whereNull('deleted_at'),
                ],
                'address'  => 'sometimes|required|string',
                'phone'    => 'nullable|string|regex:/^[0-9]{10,15}$/',
                'whatsapp' => 'nullable|string|regex:/^[0-9]{10,15}$/',
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