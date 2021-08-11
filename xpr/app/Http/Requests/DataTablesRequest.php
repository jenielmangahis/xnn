<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataTablesRequest extends FormRequest
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
            'draw' => 'required|int',
            'start' => 'required|int',
            'length' => 'required|int',
            'search' => 'required|array',
            'order' => 'required|array',
            'columns' => 'required|array',
        ];
    }
}
