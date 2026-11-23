<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Mail\Mailable;

/**
 * Synchronous so the CMS can report the real delivery result. Content is
 * rendered by TemplateRenderer before the mailable is constructed.
 */
final class InquiryReplyMail extends Mailable
{
    public function __construct(
        public readonly string $emailSubject,
        public readonly string $renderedBody,
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->emailSubject)
            ->html('<div dir="auto" style="font-family:inherit;line-height:1.6">'.nl2br(e($this->renderedBody)).'</div>')
            ->text($this->renderedBody);
    }
}
