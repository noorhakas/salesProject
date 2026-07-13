<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductRequest extends FormRequest
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
				'name'=>'required|string|max:100|unique:products,name,NULL,id,deleted_at,NULL',
				//'specialty_id'=>'required|exists:specialty,id,deleted_at,NULL',
				'category_id'=>'required|exists:category,id,deleted_at,NULL',
				'company_id'=>'required|exists:companies,id,deleted_at,NULL',
				'image'=>['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
				'description'=> 'min:10',
				'price'=>'sometimes|numeric',
				'files' =>'array',
				'files.*'=>['file', 'mimes:pdf','max:2048'], 
                                'status'=>'required|integer|in:0,1',

 
			],
            "PUT", "PATCH" =>  [
                'name' => 'sometimes|required|string|max:255|unique:products,name,' . $this->product?->id . ',id,deleted_at,NULL',
			    'specialty_id'=>'sometimes|required|exists:specialty,id',
'category_id'=>'required|exists:category,id,deleted_at,NULL',
				'company_id'=>'required|exists:companies,id,deleted_at,NULL',
				'image'=>['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
				'description'=> 'min:10',
				'price'=>'sometimes|numeric',
				'files' =>'array',
				//'files.*'=>['file', 'mimes:pdf','max:6048'], 
                                'status'=>'required|integer|in:0,1',

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
 