<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class WorkflowIndexRequest extends Phase5Request
{
    public function authorize(): bool
    {
        return $this->allows('workflows.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $statuses = array_values((array) config('phase5.workflow.statuses', []));
        $categories = array_values((array) config('phase5.workflow.categories', []));

        return [
            'status' => ['sometimes', Rule::in($statuses)],
            'category' => ['sometimes', Rule::in($categories)],
            'definition_id' => ['sometimes', 'integer', 'min:1'],
            'subject_type' => ['sometimes', 'string', 'max:96'],
            'subject_id' => ['sometimes', 'integer', 'min:1', 'required_with:subject_type'],
            'sort' => ['sometimes', Rule::in(['created_at', 'id', 'key', 'status', 'reference', 'due_at'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('subject_type')) {
                    return;
                }

                $user = $this->user();
                if ($user === null) {
                    return;
                }

                $this->phase5Auth()->assertCanViewResource(
                    $user,
                    (string) $this->input('subject_type'),
                    (int) $this->input('subject_id'),
                );
            },
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return $this->safe()->only([
            'status', 'category', 'definition_id', 'subject_type', 'subject_id',
            'sort', 'direction', 'per_page',
        ]);
    }
}
