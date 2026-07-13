<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SalesRequest extends FormRequest
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
            'account_id' => 'required|exists:accounts,id,deleted_at,NULL',
           // 'month_date' => 'required|date_format:Y-m-d',
            'product_list' => 'required|array|min:1',
            'product_list.*.product_id' => 'required|exists:products,id,deleted_at,NULL',
            'product_list.*.unit' => 'required|integer|min:1',
        ];
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
