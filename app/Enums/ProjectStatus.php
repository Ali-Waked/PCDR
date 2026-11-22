<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectStatus: string
{
    case Planned = 'planned';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(string $locale = 'ar'): string
    {
        return match ($locale) {
            'en' => match ($this) {
                self::Planned => 'Planned',
                self::Ongoing => 'Ongoing',
                self::Completed => 'Completed',
                self::Archived => 'Archived',
            },
            default => match ($this) {
                self::Planned => 'مخطط',
                self::Ongoing => 'قيد التنفيذ',
                self::Completed => 'مكتمل',
                self::Archived => 'مؤرشف',
            },
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
