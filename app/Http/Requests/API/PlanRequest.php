<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PlanRequest extends FormRequest
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

		 return [
			'visit_list'      => 'required|array',
            'visit_list.*.account_id' => 'required|exists:accounts,id,deleted_at,NULL',
			'visit_list.*.doctor_id' => 'required|exists:customers,id,deleted_at,NULL',
            'visit_list.*.visit_date'=>'required|date|after_or_equal:now',
			'visit_list.*.start_time'=>'required|date_format:H:i:s',
			'visit_list.*.end_time'=>'required|date_format:H:i:s'
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
