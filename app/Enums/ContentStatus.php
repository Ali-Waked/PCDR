<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Published = 'published';
    case Archived = 'archived';

    public function label(string $locale = 'ar'): string
    {
        return match ($locale) {
            'en' => match ($this) {
                self::Draft => 'Draft',
                self::PendingReview => 'Pending review',
                self::Approved => 'Approved',
                self::Published => 'Published',
                self::Archived => 'Archived',
            },
            default => match ($this) {
                self::Draft => 'مسودة',
                self::PendingReview => 'قيد المراجعة',
                self::Approved => 'معتمدة',
                self::Published => 'منشورة',
                self::Archived => 'مؤرشفة',
            },
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Normalize a status attribute (which Eloquent may cast to an enum) into
     * its stored string value without relying on an invalid enum-to-string cast.
     */
    public static function valueOf(mixed $status, string $fallback = self::Draft->value): string
    {
        if ($status instanceof self) {
            return $status->value;
        }

        return is_string($status) ? $status : $fallback;
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        $keys = config("workflows.transitions.{$this->value}", []);

        return array_values(array_filter(
            array_map(static fn (string $value): ?self => self::tryFrom($value), $keys),
        ));
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
