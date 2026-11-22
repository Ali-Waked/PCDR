<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ContactMessage;
use App\Models\PartnershipInquiry;
use App\Models\VolunteerInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class StoreInquiry
{
    public function handle(string $type, array $data, Request $request): mixed
    {
        $key = 'inquiry:'.$type.':'.hash('sha256', ($request->ip() ?? '').'|'.($data['email'] ?? ''));

        if (RateLimiter::tooManyAttempts($key, (int) config('website.inquiry.rate_limit', 5))) {
            abort(429, __('website.forms.too_many_requests'));
        }

        $payload = [
            'reference' => (string) Str::ulid(),
            'locale' => app()->getLocale(),
            'consent' => (bool) ($data['consent'] ?? false),
            'ip_hash' => hash('sha256', ($request->ip() ?? '').'|'.config('app.key')),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'status' => 'new',
        ];

        $record = match ($type) {
            'contact' => ContactMessage::create($payload + [
                'category' => $data['category'],
                'name' => $data['name'],
                'email' => $data['email'],
                'subject' => $data['subject'] ?? null,
                'message' => $data['message'],
            ]),
            'volunteer' => VolunteerInquiry::create($payload + [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'area_of_interest' => $data['area_of_interest'],
                'availability' => $data['availability'],
                'message' => $data['message'] ?? null,
            ]),
            'partnership' => PartnershipInquiry::create($payload + [
                'organization' => $data['organization'],
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'website' => $data['website'] ?? null,
                'interest' => $data['interest'],
                'message' => $data['message'],
            ]),
            default => throw new \InvalidArgumentException("Unknown inquiry type: {$type}"),
        };

        RateLimiter::hit($key, (int) config('website.inquiry.rate_decay_minutes', 10) * 60);

        return $record;
    }
}
