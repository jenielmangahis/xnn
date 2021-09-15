<?php


namespace App\Http\Requests\Admin\CommissionAdjustment;


use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
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
            'member_id' => 'required|integer|exists:users,id',
            'commission_period_id' => 'required|integer|exists:cm_commission_periods,id',
            'purchaser_id' => 'required|integer|exists:users,id',
            'order_id' => 'required|integer|exists:transactions,id',
        ];
    }

    public function messages()
    {
        return [
            'member_id.required' => 'Member is required.',
            'commission_period_id.required' => 'Period is required.',
            'purchaser_id.required' => 'Purchaser is required.',
            'order_id.required' => 'Order ID is required.',
        ];
    }
}