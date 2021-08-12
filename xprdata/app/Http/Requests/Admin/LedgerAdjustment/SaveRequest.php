<?php


namespace App\Http\Requests\Admin\LedgerAdjustment;


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
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'required|min:3',
            'type' => 'required|in:add,remove',
        ];
    }

    public function messages()
    {
        return [
            'user_id.required' => 'Member is required.',
        ];
    }
}