<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $adminId = $this->admin?->id;

        $rules = [
            'emp_no' => 'required',

            'name' => 'required|string|max:100',

            'user_name' => [
                'required',
                'string',
                'max:100',
                'unique:users,user_name,' . $adminId . ',id,deleted_at,NULL',
            ],

            'email' => [
                'required',
                'email:rfc,dns',
                'unique:users,email,' . $adminId . ',id,deleted_at,NULL',
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

        ];

        /*
         * Create
         */
        if ($this->isMethod('POST')) {

            $rules['password'] = 'required|min:6';
        }

        /*
         * Update
         */
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {

            $rules['password'] = 'sometimes|required|min:6';

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