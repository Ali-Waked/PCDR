<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return Gate::allows('changeStatus', $target);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['required', 'in:active,inactive,suspended']];
    }
}
