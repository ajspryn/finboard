<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OtpWhatsappNotification extends Notification
{
     use Queueable;

     public function __construct(
          private readonly string $pinCode
     ) {}

     public function via(object $notifiable): array
     {
          return [];
     }
}
