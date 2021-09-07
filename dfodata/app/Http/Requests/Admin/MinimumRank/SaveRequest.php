<?php


namespace App\Http\Requests\Admin\MinimumRank;


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
            'rank_id' => 'required|integer|exists:cm_ranks,id',
            'start_date' => 'required|date_format:"Y-m-d"|before_or_equal:end_date',
            'end_date' => 'required|date_format:"Y-m-d"',
        ];
    }

    public function messages()
    {
        return [
            'user_id.required' => 'Member is required.',
            'rank_id.required' => 'Rank is required.',
        ];
    }
}