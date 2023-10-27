<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClassesRequest extends FormRequest
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
		return match(request()->method()){
            "POST" => [
				'name'=>'required|string|max:100|unique:classes,name,NULL,id,deleted_at,NULL',
				'frequency'=>'required|integer|min:1'
			],
            "PUT", "PATCH" =>  [
                'name' => 'sometimes|required|string|max:255|unique:classes,name,' . $this->class . ',id,deleted_at,NULL',
				'frequency'=>'required|integer|min:1'
			],
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
