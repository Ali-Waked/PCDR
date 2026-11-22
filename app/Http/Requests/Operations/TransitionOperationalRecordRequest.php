<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

use App\Exceptions\InvalidDomainTransition;
use Illuminate\Validation\Validator;

final class TransitionOperationalRecordRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        $resource = $this->routeResource();
        if ($resource === null) {
            return false;
        }

        return $this->allows($this->catalog()->permission($resource, 'transition'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'to_status' => ['required', 'string', 'max:64'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'evidence_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }

    /** @return array<int, \Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $resource = $this->routeResource();
                if ($resource === null) {
                    return;
                }

                $record = $this->catalog()->record($resource)->newQuery()
                    ->whereKey($this->route('record'))
                    ->first();
                if ($record === null) {
                    return;
                }

                try {
                    $this->catalog()->assertTransition(
                        $resource,
                        $record->getAttribute('status') === null ? null : (string) $record->getAttribute('status'),
                        (string) $this->input('to_status'),
                    );
                } catch (InvalidDomainTransition) {
                    $validator->errors()->add('to_status', 'The requested status transition is not allowed.');
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function transitionInput(): array
    {
        return $this->safe()->only(['to_status', 'reason', 'evidence_media_id']);
    }
}
