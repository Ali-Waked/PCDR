<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Models\Media;
use App\Services\Phase5\Phase5Authorization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class TransitionDocumentReviewRequest extends FormRequest
{
    public function authorize(Phase5Authorization $authorization): bool
    {
        $user = $this->user();

        return $user !== null && $authorization->allows($user, 'documents.review');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isResubmission = $this->string('to_status')->toString() === 'resubmitted';

        return [
            'to_status' => ['required', 'string', Rule::in((array) config('phase5.document_review.statuses', []))],
            'note' => ['nullable', 'string', 'max:'.(int) config('phase5.document_review.max_review_chars', 5000)],
            'media_id' => [
                Rule::requiredIf($isResubmission),
                'nullable',
                'integer',
                'exists:media,id',
            ],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || $this->string('to_status')->toString() !== 'resubmitted') {
                    return;
                }

                /** @var Media|null $media */
                $media = Media::query()->whereKey((int) $this->input('media_id'))->where('is_restricted', true)->first();

                if ($media === null) {
                    $validator->errors()->add('media_id', 'Resubmissions require a restricted document.');
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function validatedData(): array
    {
        return $this->safe()->only(['to_status', 'note', 'media_id']);
    }
}
