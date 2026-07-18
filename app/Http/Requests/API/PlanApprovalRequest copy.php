<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PlanApprovalRequest extends FormRequest
{
    public function authorize()
    {
        return true; // الفلترة الحقيقية بتحصل في authorizeManagedPlan بالكنترولر
    }

    public function rules()
    {
        $rules = [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ];

        // لو الروت هو reject ممكن تحب تطلب سبب الرفض
        if ($this->routeIs('*plans.reject') || $this->is('*plans/reject')) {
            $rules['reject_reason'] = ['required', 'string', 'max:500'];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'plan_id.required' => trans('messages.plan_id_required'),
            'plan_id.exists'   => trans('messages.data_not_found'),
            'reject_reason.required' => trans('messages.reject_reason_required'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->response_api_static(false, $validator->errors()->first())
        );
    }

    // helper لو مش عندك response_api متاحة بشكل static
    protected function response_api_static($status, $message, $data = null)
    {
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ], 422);
    }
}