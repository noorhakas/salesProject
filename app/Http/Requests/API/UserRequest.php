<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
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
		$rules = [
            'name'=>'required|string|max:100',
            'user_name'=>'required|string|max:100|unique:users,user_name,NULL,id,deleted_at,NULL',
            'email'=>'required|email:rfc,dns|unique:users,email,NULL,id,deleted_at,NULL',
            'status'=>'required|integer|in:0,1',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|min:6'
        ];
        // if($this->method() == 'PUT' || $this->method() == 'PATCH'){
        //     $admin = $this->admin;
        //     $rules = [
        //         'name'=>'required|string|min:3|max:150',
        //         'username'=>'required|string|max:100|unique:admins,users,'.$admin->id.',id,deleted_at,NULL',
        //         'email'=>'required|email|unique:admins,email,'.$admin->id.',id,deleted_at,NULL',
        //         'active'=>'required|integer|in:0,1',
        //         'password'=>'sometimes|nullable|string|
        //         |min:6',
        //         'image'=>'sometimes|nullable|image|mimes:png,jpg,jpeg',
        //     ];
        // }
        return $rules;


        return [
            'name' => 'required',
			'permissions' => 'required|array|between:1,100',
            'permissions.*.id' => 'exists:permissions,id'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(response()->json(
            ['status'=>false ,'errors' => $errors
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}
