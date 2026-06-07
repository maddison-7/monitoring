<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function __construct(private readonly OutboundChannelService $outboundChannelService)
    {
    }

    public function notify(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?int $relatedId = null,
        bool $sendEmail = true,
        bool $sendSms = true
    ): Notification
    {
        $notification = Notification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $relatedId,
        ]);

        $user = User::query()->with('applicant')->find($userId);
        if ($user instanceof User) {
            if ($sendEmail && !empty($user->email)) {
                $this->outboundChannelService->sendEmail((string) $user->email, $title, $message);
            }

            $phone = (string) optional($user->applicant)->phone;
            if ($sendSms && $phone !== '') {
                $this->outboundChannelService->sendSms($phone, $title . ': ' . $message);
            }
        }

        return $notification;
    }
}
