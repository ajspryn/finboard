<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WhatsApp\Component;
use NotificationChannels\WhatsApp\WhatsAppChannel;
use NotificationChannels\WhatsApp\WhatsAppTemplate;

class OtpWhatsappNotification extends Notification
{
     use Queueable;

     public function __construct(
          private readonly string $pinCode
     ) {}

     public function via(object $notifiable): array
     {
          return [WhatsAppChannel::class];
     }

     public function toWhatsApp(object $notifiable): WhatsAppTemplate
     {
          return WhatsAppTemplate::create()
               ->name(config('services.whatsapp.otp_template_name', 'finboard_otp_code'))
               ->language(config('services.whatsapp.otp_template_language', 'id'))
               ->body(Component::text($this->pinCode))
               ->body(Component::text((string) config('services.whatsapp.otp_expired_in_minutes', 10)));
     }
}
