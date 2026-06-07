<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OutboundChannelService
{
    public function sendEmail(string $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::warning('Email dispatch failed.', [
                'to' => $to,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function sendSms(string $phone, string $body): void
    {
        $sid = (string) config('services.twilio.sid', '');
        $token = (string) config('services.twilio.token', '');
        $from = (string) config('services.twilio.from', '');

        if ($sid === '' || $token === '' || $from === '') {
            Log::info('Twilio credentials not configured. SMS dispatch skipped.', [
                'phone' => $phone,
            ]);

            return;
        }

        try {
            Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $phone,
                    'Body' => $body,
                ])
                ->throw();
        } catch (Throwable $exception) {
            Log::warning('SMS dispatch failed.', [
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
