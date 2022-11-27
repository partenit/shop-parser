<?php

namespace App\Listeners;

use App\Events\RequestPageContentFailed;
use App\Mail\ReportParseError;
use Exception;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AfterRequestPageContentFailed
{
    /**
     * Handle the event.
     *
     * @param RequestPageContentFailed $event
     * @return void
     * @throws Exception
     */
    public function handle(RequestPageContentFailed $event)
    {
        if ($event->response->status() === Response::HTTP_SERVICE_UNAVAILABLE) {
            $this->sendEmail($event);
        }

        Log::info(
            'Ошибка при запросе страницы',
            [
                'status' => $event->response->status(),
                'url' => $event->url,
                'headers' => $event->response->headers(),
            ]
        );
    }

    private function sendEmail($event): void
    {
        $email = config('parse.email');

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Mail::to($email)
                ->queue(new ReportParseError($event));
        }
    }
}
