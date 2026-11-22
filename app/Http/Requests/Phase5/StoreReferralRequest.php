<?php

declare(strict_types=1);

namespace App\Http\Requests\Phase5;

use App\Services\Phase5\Phase5Authorization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreReferralRequest extends FormRequest
{
    public function authorize(Phase5Authorization $authorization): bool
    {
        $user = $this->user();

        return $user !== null && $authorization->allows($user, 'referrals.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source_type' => ['required', 'string', Rule::in((array) config('phase5.referral.source_types', []))],
            'source_id' => ['required', 'integer', 'min:1'],
            'destination_type' => ['required', 'string', Rule::in((array) config('phase5.referral.destination_types', []))],
            'destination_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:'.(int) config('phase5.referral.max_reason_chars', 4000)],
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

                if ($this->string('source_type')->toString() === 'operational_record') {
                    $validator->errors()->add('source_type', 'This referral source type has no addressable record.');
                }

                $destinationType = $this->string('destination_type')->toString();
                $destinationId = (int) $this->input('destination_id');

                $approved = match ($destinationType) {
                    'partner' => DB::table('partners')
                        ->where('id', $destinationId)
                        ->whereNull('deleted_at')
                        ->where('relationship_status', 'active')
                        ->exists(),
                    'internal_team' => DB::table('users')
                        ->where('id', $destinationId)
                        ->whereNull('deleted_at')
                        ->where('status', 'active')
                        ->exists(),
                    'service' => DB::table('interventions')
                        ->where('id', $destinationId)
                        ->whereNull('deleted_at')
                        ->whereIn('status', ['active', 'completed'])
                        ->exists(),
                    default => false,
                };

                if (! $approved) {
                    $validator->errors()->add('destination_id', 'The selected destination is not an approved active entity.');
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function validatedData(): array
    {
        return $this->safe()->only([
            'source_type',
            'source_id',
            'destination_type',
            'destination_id',
            'reason',
            'due_at',
        ]);
    }
}
