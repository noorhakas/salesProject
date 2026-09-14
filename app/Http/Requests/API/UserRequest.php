<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->user?->id;

        $rules = [
            'emp_no' => 'required',

            'name' => 'required|string|max:100',

            'user_name' => [
                'required',
                'string',
                'max:100',
                'unique:users,user_name,' . $userId . ',id,deleted_at,NULL',
            ],

            'email' => [
                'required',
                'email:rfc,dns',
                'unique:users,email,' . $userId . ',id,deleted_at,NULL',
            ],

            'phone' => [
                'nullable',
                'max:20',
            ],

            'whatsapp' => [
                'nullable',
                'max:20',
            ],

            'status' => 'required|integer|in:0,1',

            'role_id' => 'sometimes|exists:roles,id',

            'position' => 'sometimes',

            'manager_id' => 'sometimes',
        ];

        /*
         * Create
         */
        if ($this->isMethod('POST')) {

            $rules['password'] = 'required|min:6';

            $rules['file'] = 'required|file|mimes:xls,xlsx';
        }

        /*
         * Update
         */
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {

            $rules['password'] = 'sometimes|required|min:6';

            $rules['file'] = 'sometimes|file|mimes:xls,xlsx';
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();

        throw new HttpResponseException(
            response()->json(
                [
                    'status' => false,
                    'errors' => $errors,
                ],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            )
        );
    }
}