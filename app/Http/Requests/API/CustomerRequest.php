<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CustomerRequest extends FormRequest
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
			'account_id'=>'required|exists:accounts,id,deleted_at,NULL',
			'acc_type_id'=>'required|exists:acc_type,id,deleted_at,NULL',
			'specialty_id'=>'sometimes|exists:specialty,id,deleted_at,NULL',
			'class_id'=>'required|exists:classes,id,deleted_at,NULL',
			'image'=>['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
			'phone'=>'required|numeric|digits_between:6,14|unique:customers,phone,NULL,id,deleted_at,NULL',
			'phone1'=>'numeric|digits_between:6,14|unique:customers,phone,NULL,id,deleted_at,NULL',
			'brief'=>'sometimes|string',
		//	'work_days'=>'sometimes',
			'work_start_time'=>'sometimes',
			'work_end_time'=>'sometimes',
		];
		
		return match(request()->method()){
			"POST" => $base,
            "PUT", "PATCH" => array_merge($base,['name' => 'sometimes|required|string|max:255|unique:customers,name,' . $this->customer->id . ',id,deleted_at,NULL'
		                         ,'phone' => 'numeric|digits_between:6,14|unique:customers,phone,' . $this->customer->id . ',id,deleted_at,NULL'
								 ,'phone1' => 'numeric|digits_between:6,14|unique:customers,phone1,' . $this->customer->id . ',id,deleted_at,NULL'
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
