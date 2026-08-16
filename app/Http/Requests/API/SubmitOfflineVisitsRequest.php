<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class SubmitOfflineVisitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية بتتحقق جوه الـ Repository (user_id match)
    }

    public function rules(): array
    {
        return [
            'visits'                          => ['required', 'array', 'min:1'],
            'visits.*.visit_id'                => ['required', 'integer', 'exists:visits,id'],
            'visits.*.doctor_id'               => ['nullable', 'integer', 'exists:customers,id'],
            'visits.*.combine_with'            => ['nullable', 'integer'],
            'visits.*.start_time'              => ['required', 'date'],
            'visits.*.end_time'                => ['required', 'date', 'after_or_equal:visits.*.start_time'],
            'visits.*.current_location_lat'    => ['nullable', 'numeric', 'between:-90,90'],
            'visits.*.current_location_lng'    => ['nullable', 'numeric', 'between:-180,180'],
            'visits.*.notes'                   => ['nullable', 'string'],
            'visits.*.items'                   => ['nullable', 'array'],
            'visits.*.items.*.item_id'         => ['required_with:visits.*.items', 'integer'],
            'visits.*.items.*.sample'          => ['nullable', 'integer', 'min:0'],
            'visits.*.items.*.item_type'       => ['required_with:visits.*.items', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'visits.required'   => __('messages.visits_required'),
            'visits.*.visit_id.exists' => __('messages.visit_not_found'),
        ];
    }
}