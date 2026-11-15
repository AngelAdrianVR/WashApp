<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingFlowMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array{subject: string, eyebrow: string, title: string, intro: string, rows: array<int, array{label: string, value: string}>, highlight?: array{title: string, lines: array<int, string>, tone?: string}|null, cta?: array{label: string, url: string}|null, footer?: string|null}  $payload
     */
    public function __construct(public array $payload)
    {
    }

    public function build(): self
    {
        return $this
            ->subject($this->payload['subject'])
            ->view('emails.booking-flow', ['payload' => $this->payload]);
    }
}
