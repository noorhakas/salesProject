<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AccountRequest extends FormRequest
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

        $base = [
			'name'=>'required|string|max:100|unique:customers,name,NULL,id,deleted_at,NULL',
			'brick_id'=>'required|exists:bricks,id',
			'acc_type_id'=>'required|exists:acc_type,id',
			'class_id'=>'required|exists:classes,id',
			'phone'=>'required|numeric|digits_between:6,14|unique:customers,phone,NULL,id,deleted_at,NULL',
			'phone1'=>'numeric|digits_between:6,14|unique:customers,phone,NULL,id,deleted_at,NULL',
			'address'=>'required|string',
			'lat'=>'required',
			'lng'=>'required'
		];
		
		return match(request()->method()){
			"POST" => $base,
            "PUT", "PATCH" => array_merge($base,['name' => 'sometimes|required|string|max:255|unique:accounts,name,' . $this->account->id . ',id,deleted_at,NULL'
		                         ,'phone' => 'numeric|digits_between:6,14|unique:accounts,phone,' . $this->account->id . ',id,deleted_at,NULL'
								 ,'phone1' => 'numeric|digits_between:6,14|unique:accounts,phone1,' . $this->account->id . ',id,deleted_at,NULL'
								]),
        };
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(response()->json(
            ['status'=>false ,'errors' => $errors
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}
