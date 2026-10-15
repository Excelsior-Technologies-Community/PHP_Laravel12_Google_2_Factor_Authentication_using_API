<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityAlertNotification extends Notification
{
    use Queueable;

    public string $type;
    public string $title;
    public array $details;

    public function __construct(string $type, string $title, array $details = [])
    {
        $this->type = $type;
        $this->title = $title;
        $this->details = $details;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[Security Alert] {$this->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->title);

        foreach ($this->details as $label => $value) {
            $mail->line("**{$label}:** {$value}");
        }

        $mail->line('If you did not perform or authorize this activity, please secure your account immediately.');

        return $mail;
    }
}
