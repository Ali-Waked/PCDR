<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

use Illuminate\Validation\Rule;

final class AttachOperationalDocumentRequest extends OperationalRequest
{
    public function authorize(): bool
    {
        return $this->allows('documents.upload');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $mimes = config('website.uploads.allowed_document_mimes', []);
        $mimes = is_array($mimes) ? implode(',', $mimes) : 'pdf,doc,docx';

        return [
            'document' => ['required', 'file', 'mimes:'.$mimes, 'max:'.(int) config('website.uploads.max_file_size_kb', 10240)],
            'usage' => ['nullable', 'string', Rule::in(['document', 'evidence', 'submission', 'agreement'])],
        ];
    }
}
