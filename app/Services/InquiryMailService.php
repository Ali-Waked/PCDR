<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\EmailDeliveryLog;
use App\Models\EmailTemplate;
use App\Models\InquiryReply;
use App\Models\PartnershipInquiry;
use App\Models\VolunteerInquiry;
use App\Notifications\InquiryReplyMail;
use Illuminate\Support\Facades\Mail;
use Throwable;

class InquiryMailService
{
    public function __construct(
        private TemplateRenderer $renderer,
        private EmailTemplateContentResolver $content,
    ) {}

    public function composeReply(
        object $inquiry,
        string $inquiryType,
        string $subject,
        string $message,
        string $locale,
        ?int $templateId,
        ?int $senderUserId,
    ): InquiryReply {
        $template = $templateId !== null
            ? EmailTemplate::query()->whereKey($templateId)->where('is_active', true)->first()
            : $this->template('inquiry_reply');
        $key = $template?->key ?: 'inquiry_reply';
        $variables = $this->variables($inquiry, $subject, $message, $locale);
        $renderedSubject = $this->render($template, $key, 'subject', $locale, $variables);
        $renderedBody = $this->render($template, $key, 'body', $locale, $variables);

        return InquiryReply::create([
            'inquiry_type' => $inquiryType,
            'inquiry_id' => $inquiry->id,
            'reference' => $inquiry->reference,
            'recipient' => $inquiry->email,
            'sender_user_id' => $senderUserId,
            'email_template_id' => $template?->id,
            'subject' => $renderedSubject !== '' ? $renderedSubject : $subject,
            'body' => $renderedBody !== '' ? $renderedBody : $message,
            'locale' => $locale,
            'status' => 'pending',
        ]);
    }

    public function sendReply(InquiryReply $reply): EmailDeliveryLog
    {
        $modelClass = $this->inquiryClass($reply->inquiry_type);
        $inquiry = $modelClass::query()->findOrFail($reply->inquiry_id);
        $locale = $reply->locale ?: $this->locale($inquiry);
        $templateKey = $reply->template?->key ?: 'inquiry_reply';
        $idempotencyKey = hash('sha256', 'inquiry_reply:'.$reply->id);
        $existing = EmailDeliveryLog::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing !== null) {
            return $existing;
        }

        $log = EmailDeliveryLog::create([
            'idempotency_key' => $idempotencyKey,
            'purpose' => 'inquiry_reply',
            'template_key' => $templateKey,
            'inquiry_type' => $reply->inquiry_type,
            'inquiry_id' => $inquiry->id,
            'inquiry_reference' => $inquiry->reference,
            'recipient_hash' => $this->hashRecipient($inquiry->email),
            'locale' => $locale,
            'status' => 'pending',
            'attempts' => 1,
            'sender_user_id' => $reply->sender_user_id,
            'queued_at' => now(),
        ]);

        try {
            Mail::to($inquiry->email)->send(new InquiryReplyMail(
                $reply->subject,
                $reply->body,
                $locale,
                (string) ($reply->reference ?? ''),
            ));
            $reply->forceFill(['status' => 'sent', 'sent_at' => now(), 'error' => null])->save();
            $this->markSent($log);
        } catch (Throwable $exception) {
            $message = mb_strcut($exception->getMessage(), 0, 1000);
            $reply->forceFill(['status' => 'failed', 'error' => $message])->save();
            $this->markFailed($log, $message);
        }

        return $log->fresh();
    }

    public function sendInternalNotification(object $inquiry, string $inquiryType): ?EmailDeliveryLog
    {
        $recipient = config('email.internal_notification_recipient');

        if (! is_string($recipient) || $recipient === '') {
            return null;
        }

        $locale = config('website.default_locale', 'ar');
        $template = $this->template('inquiry_received_notification');
        $variables = [
            'inquiry_reference' => $inquiry->reference,
            'organization_name' => $this->organizationName(),
            'current_date' => now()->locale($locale)->translatedFormat('j F Y'),
        ];
        $subject = $this->render($template, 'inquiry_received_notification', 'subject', $locale, $variables);
        $body = $this->render($template, 'inquiry_received_notification', 'body', $locale, $variables);
        $idempotencyKey = hash('sha256', 'internal_notification:'.$inquiryType.':'.$inquiry->id);
        $existing = EmailDeliveryLog::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing !== null) {
            return $existing;
        }

        $log = EmailDeliveryLog::create([
            'idempotency_key' => $idempotencyKey,
            'purpose' => 'internal_notification',
            'template_key' => $template?->key ?: 'inquiry_received_notification',
            'inquiry_type' => $inquiryType,
            'inquiry_id' => $inquiry->id,
            'inquiry_reference' => $inquiry->reference,
            'recipient_hash' => $this->hashRecipient($recipient),
            'locale' => $locale,
            'status' => 'pending',
            'attempts' => 1,
            'queued_at' => now(),
        ]);

        try {
            Mail::to($recipient)->send(new InquiryReplyMail(
                $subject,
                $body,
                $locale,
                (string) ($inquiry->reference ?? ''),
            ));
            $this->markSent($log);
        } catch (Throwable $exception) {
            $this->markFailed($log, $exception->getMessage());
        }

        return $log->fresh();
    }

    /** @return array<string, string> */
    public function availableVariables(): array
    {
        return $this->renderer->availableVariables();
    }

    private function template(string $key): ?EmailTemplate
    {
        return EmailTemplate::query()->where('key', $key)->where('is_active', true)->first();
    }

    /** @param array<string, string> $variables */
    private function render(?EmailTemplate $template, string $key, string $field, string $locale, array $variables): string
    {
        return $this->content->field($template, $key, $field, $locale, $variables);
    }

    /** @return array<string, string> */
    private function variables(object $inquiry, string $subject, string $message, string $locale): array
    {
        return [
            'sender_name' => (string) ($inquiry->name ?? $inquiry->contact_name ?? $inquiry->organization ?? ''),
            'sender_email' => (string) ($inquiry->email ?? ''),
            'inquiry_subject' => $subject,
            'inquiry_reference' => (string) ($inquiry->reference ?? ''),
            'reply_message' => $message,
            'organization_name' => $this->organizationName(),
            'current_date' => now()->locale($locale)->translatedFormat('j F Y'),
        ];
    }

    private function inquiryClass(string $type): string
    {
        return match ($type) {
            'contact' => ContactMessage::class,
            'volunteer' => VolunteerInquiry::class,
            'partnership' => PartnershipInquiry::class,
            default => abort(404),
        };
    }

    private function locale(object $inquiry): string
    {
        $locale = $inquiry->locale ?? null;

        return is_string($locale) && $locale !== '' ? $locale : config('website.default_locale', 'ar');
    }

    private function organizationName(): string
    {
        $locale = config('website.default_locale', 'ar');
        $fallback = app(SiteSettings::class)->value('organization.name', null, $locale)
            ?: (string) config("website.name.{$locale}", config('app.name'));

        return $fallback;
    }

    private function hashRecipient(string $recipient): string
    {
        return hash('sha256', mb_strtolower($recipient));
    }

    private function markSent(EmailDeliveryLog $log): void
    {
        $log->forceFill([
            'status' => 'sent',
            'sent_at' => now(),
            'failed_at' => null,
            'error_code' => null,
        ])->save();
    }

    private function markFailed(EmailDeliveryLog $log, string $message): void
    {
        $log->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'error_code' => mb_strcut($message, 0, 1000),
        ])->save();
    }
}
