<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OtpWhatsappNotification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppOtpSender
{
     public function sendOtp(User $user, string $pinCode): void
     {
          $provider = strtolower((string) config('services.whatsapp.provider', 'meta'));

          if ($provider === 'meta') {
               $this->sendViaMeta($user, $pinCode);
               return;
          }

          if ($provider === 'fonte') {
               $this->sendViaFonte($user, $pinCode);
               return;
          }

          throw new RuntimeException("Unsupported WhatsApp provider: {$provider}");
     }

     private function sendViaMeta(User $user, string $pinCode): void
     {
          $user->notify(new OtpWhatsappNotification($pinCode));
     }

     private function sendViaFonte(User $user, string $pinCode): void
     {
          $token = (string) config('services.whatsapp.fonte.token');
          $endpoint = (string) config('services.whatsapp.fonte.endpoint', 'https://api.fonnte.com/send');

          if ($token === '') {
               throw new RuntimeException('FONTE token is missing. Please set WHATSAPP_FONTE_TOKEN.');
          }

          $messageTemplate = (string) config(
               'services.whatsapp.otp_message_template',
               "*Kode PIN Login - :app*\\n\\nAssalamu'alaikum Wr. Wb.\\n\\nAnda telah meminta kode PIN untuk login ke akun :app. Berikut adalah kode PIN Anda:\\n\\n*Kode PIN:* *:otp*\\n\\n*Catatan Keamanan:*\\n- Kode PIN ini bersifat rahasia dan hanya untuk Anda\\n- Kode PIN akan kadaluarsa dalam *:minutes menit*\\n- Jangan bagikan kode ini dengan siapapun\\n\\n*Peringatan:* Jika Anda tidak meminta kode PIN ini, segera hubungi administrator sistem.\\n\\nMasukkan kode PIN di halaman login untuk melanjutkan.\\nJika kode PIN sudah kadaluarsa, Anda dapat meminta kode baru.\\n\\nTerima kasih,\\n*Tim :app*"
          );
          $expiresInMinutes = (string) config('services.whatsapp.otp_expired_in_minutes', 10);
          $appName = (string) config('app.name', 'FinBoard');

          $messageTemplate = str_replace('\\n', PHP_EOL, $messageTemplate);

          $message = strtr($messageTemplate, [
               ':app' => $appName,
               ':name' => $user->name,
               ':otp' => $pinCode,
               ':minutes' => $expiresInMinutes,
          ]);

          $response = Http::asForm()
               ->withHeaders([
                    'Authorization' => $token,
               ])
               ->post($endpoint, [
                    'target' => $user->whatsapp_number,
                    'message' => $message,
                    'countryCode' => '62',
               ]);

          if (!$response->successful()) {
               throw new RuntimeException('Fonte request failed with status ' . $response->status() . ': ' . $response->body());
          }

          $json = $response->json();
          if (is_array($json) && array_key_exists('status', $json)) {
               $status = $json['status'];
               $ok = $status === true || $status === 1 || $status === 'true' || $status === 'success';
               if (!$ok) {
                    throw new RuntimeException('Fonte returned non-success status: ' . $response->body());
               }
          }
     }
}
