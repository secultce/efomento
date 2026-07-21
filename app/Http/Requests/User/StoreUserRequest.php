<?php

namespace App\Http\Requests\User;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'cpf' => ['nullable', 'string', 'max:14', Rule::unique('users', 'cpf')],
            'registration_number' => ['nullable', 'string', 'max:30', Rule::unique('users', 'registration_number')],
            'role' => ['required', 'string', Rule::in(Role::values())],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Informe um email válido.',
            'email.unique' => 'Este email já está em uso.',
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'A confirmação de senha não coincide.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'registration_number.unique' => 'Esta matrícula já está cadastrada.',
            'role.required' => 'Selecione uma função para o usuário.',
            'role.in' => 'A função informada não existe.',
            'photo.image' => 'O arquivo enviado precisa ser uma imagem.',
            'photo.mimes' => 'A foto deve estar em formato JPEG, PNG ou WEBP.',
            'photo.max' => 'A foto não pode exceder 5MB.',
        ];
    }
}
