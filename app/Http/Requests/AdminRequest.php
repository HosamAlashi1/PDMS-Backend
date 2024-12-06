<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        $routeName = $this->route()->getName();

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[0-9]{10,15}$/|unique:users,phone,' . $this->route('admin'),
            'email' => 'required|email|max:255|unique:users,email,' . $this->route('admin'),
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];

        if ($routeName === 'admin.create') {
            $rules['password'] = 'required|string|min:8|confirmed';
        } elseif ($routeName === 'admin.update') {
            $rules['password'] = 'nullable|string|min:8|confirmed';
        }

        return $rules;
    }

}
