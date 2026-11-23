<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ContactMessage;
use App\Models\PartnershipInquiry;
use App\Models\User;
use App\Models\VolunteerInquiry;
use App\Notifications\InquiryReceivedNotification;
use App\Services\InquiryMailService;
use App\Services\RbacService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class StoreInquiry
{
    public function __construct(private InquiryMailService $mailService) {}

    public function handle(string $type, array $data, Request $request): mixed
    {
        $ip = $request->ip() ?? '';
        $key = 'inquiry:'.$type.':'.hash('sha256', $ip.'|'.($data['email'] ?? ''));
        $ipKey = 'inquiry:ip:'.hash('sha256', $ip);

        if (RateLimiter::tooManyAttempts($key, (int) config('website.inquiry.rate_limit', 5))
            || RateLimiter::tooManyAttempts($ipKey, (int) config('website.inquiry.ip_rate_limit', 20))) {
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

        $decay = (int) config('website.inquiry.rate_decay_minutes', 10) * 60;
        RateLimiter::hit($key, $decay);
        RateLimiter::hit($ipKey, $decay);
        $this->mailService->sendInternalNotification($record, $type);
        $this->notifyInquiryReviewers($record, $type);

        return $record;
    }

    private function notifyInquiryReviewers(object $record, string $type): void
    {
        $rbac = app(RbacService::class);
        $notification = new InquiryReceivedNotification($type, (string) $record->reference, (int) $record->id);

        User::query()->where('status', User::STATUS_ACTIVE)
            ->get()
            ->filter(fn (User $user): bool => $rbac->has($user, 'inquiries.view'))
            ->each(fn (User $user) => $user->notify($notification));
    }
}
