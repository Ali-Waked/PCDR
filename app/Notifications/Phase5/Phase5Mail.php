<?php

declare(strict_types=1);

namespace App\Notifications\Phase5;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Lang;

/** Queued, content-free mail: subjects and bodies are fixed localized notices. */
final class Phase5Mail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $titleKey,
        public readonly string $previewKey,
        public readonly ?string $recipientLocale = null,
    ) {
        $this->afterCommit();
    }

    public function build(): self
    {
        $locale = $this->recipientLocale ?: app()->getLocale();
        $key = 'phase5.notification.'.$this->titleKey;
        $subject = Lang::has($key, $locale)
            ? (string) __($key, [], $locale)
            : (string) __('phase5.created', [], $locale);
        $body = (string) __('phase5.privacy_notice', [], $locale);

        return $this
            ->subject($subject)
            ->html('<p>'.e($subject).'</p><p>'.e($body).'</p>');
    }
}
