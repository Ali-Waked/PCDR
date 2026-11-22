<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Models\DocumentLink;
use App\Models\Media;
use App\Services\Phase5\Phase5Authorization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreDocumentReviewRequest extends FormRequest
{
    public function authorize(Phase5Authorization $authorization): bool
    {
        $user = $this->user();

        return $user !== null && $authorization->allows($user, 'documents.submit_review');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var list<string> $subjectTypes */
        $subjectTypes = array_keys((array) config('phase5.collaboration.resource_permissions', []));

        return [
            'media_id' => ['required', 'integer', 'exists:media,id'],
            'document_link_id' => ['nullable', 'integer', 'exists:document_links,id'],
            'subject_type' => ['nullable', 'string', 'max:96', Rule::requiredIf($this->filled('subject_id')), Rule::in($subjectTypes)],
            'subject_id' => ['nullable', 'integer', 'min:1', Rule::requiredIf($this->filled('subject_type'))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:'.(int) config('phase5.document_review.max_review_chars', 5000)],
            'due_at' => ['nullable', 'date'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $mediaId = (int) $this->input('media_id');
                /** @var Media|null $media */
                $media = Media::query()->whereKey($mediaId)->where('is_restricted', true)->first();

                if ($media === null) {
                    $validator->errors()->add('media_id', 'Only restricted documents may be submitted for review.');

                    return;
                }

                if ($this->filled('document_link_id')) {
                    /** @var DocumentLink|null $link */
                    $link = DocumentLink::query()
                        ->whereKey((int) $this->input('document_link_id'))
                        ->with('media')
                        ->first();

                    if ($link === null || (int) $link->media_id !== $mediaId) {
                        $validator->errors()->add('document_link_id', 'The document link does not match the submitted media.');
                    }
                }

                if ($this->string('subject_type')->toString() === 'operational_record') {
                    $validator->errors()->add('subject_type', 'This review subject type has no addressable record.');
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function validatedData(): array
    {
        return $this->safe()->only([
            'media_id',
            'document_link_id',
            'subject_type',
            'subject_id',
            'title',
            'description',
            'due_at',
        ]);
    }
}
