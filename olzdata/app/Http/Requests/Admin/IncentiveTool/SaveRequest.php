<?php


namespace App\Http\Requests\Admin\IncentiveTool;


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
            'title' => 'required',
            'description' => 'required',
            'start_date' => 'required|date_format:"Y-m-d"',
            'end_date' => 'required|date_format:"Y-m-d"',
        ];
    }

    public function messages()
    {
        return [
            'start_date.required' => 'Period is Required.',
            'end_date.required' => 'Period is Required.',
        ];
    }
}