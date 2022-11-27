<?php

namespace App\Mail;

use App\Events\RequestPageContentFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportParseError extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
    * @param RequestPageContentFailed $event
    */
    public function __construct(public RequestPageContentFailed $event) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->view('emails.report_parse_error')
            ->subject('Ошибка при парсинге страницы');
    }
}
