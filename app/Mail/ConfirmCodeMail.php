<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmCodeMail extends Mailable
{
    use Queueable, SerializesModels;


    public function __construct(public string $code, public $subject)
    {
    }

    public function build(): self
    {
        return $this->subject($this->subject)
            ->markdown('emails.confirm_code')
            ->with('code', $this->code);
    }
}
