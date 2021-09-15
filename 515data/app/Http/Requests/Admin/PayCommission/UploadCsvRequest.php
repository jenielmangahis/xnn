<?php


namespace App\Http\Requests\Admin\PayCommission;


use Illuminate\Foundation\Http\FormRequest;

class UploadCsvRequest extends FormRequest
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
            'csv_file' => 'required|file|mimes:csv,txt|max:500000',
        ];
    }

    public function messages()
    {
        return [
            'csv_file.required' => 'Payout file is required.',
            'csv_file.mimes' => 'Payout file must be a CSV file.',
        ];
    }

    public function withValidator($validator)
    {
//        $validator->after(function ($validator) {
//
//            if($this->hasFile('csv_file') && strtoupper($this->hasFile('csv_file')->getClientOriginalExtension()) !== "CSV") {
//                $validator->errors()->add('csv_file', "Payout file must be a CSV file.");
//            }
//
//        });
    }
}