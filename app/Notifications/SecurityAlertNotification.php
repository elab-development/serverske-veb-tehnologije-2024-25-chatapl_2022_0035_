<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SecurityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $alertType;
    protected $details;
    protected $ipAddress;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $alertType, array $details, string $ipAddress = null)
    {
        $this->alertType = $alertType;
        $this->details = $details;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Sigurnosno upozorenje: ' . $this->getAlertTitle())
            ->greeting('Zdravo ' . $notifiable->name . '!')
            ->line('Detektovana je sumnjiva aktivnost na vašem nalogu.');

        switch ($this->alertType) {
            case 'failed_login':
                $message->line('Neuspešan pokušaj prijave sa IP adrese: ' . $this->ipAddress);
                break;
            case 'multiple_failed_logins':
                $message->line('Više neuspešnih pokušaja prijave sa IP adrese: ' . $this->ipAddress);
                break;
            case 'unusual_activity':
                $message->line('Detektovana je neobična aktivnost: ' . $this->details['activity']);
                break;
            case 'suspicious_ip':
                $message->line('Prijava sa sumnjive IP adrese: ' . $this->ipAddress);
                break;
        }

        $message->action('Proveri aktivnost', url('/security/activity'))
            ->line('Ako niste vi, odmah promenite lozinku!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alert_type' => $this->alertType,
            'details' => $this->details,
            'ip_address' => $this->ipAddress,
            'type' => 'security_alert',
            'created_at' => now(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'alert_type' => $this->alertType,
            'details' => $this->details,
            'ip_address' => $this->ipAddress,
            'type' => 'security_alert',
            'created_at' => now(),
        ]);
    }

    /**
     * Get the alert title based on type.
     */
    private function getAlertTitle(): string
    {
        return match($this->alertType) {
            'failed_login' => 'Neuspešan pokušaj prijave',
            'multiple_failed_logins' => 'Više neuspešnih pokušaja prijave',
            'unusual_activity' => 'Neobična aktivnost',
            'suspicious_ip' => 'Prijava sa sumnjive IP adrese',
            default => 'Sigurnosno upozorenje',
        };
    }
} 