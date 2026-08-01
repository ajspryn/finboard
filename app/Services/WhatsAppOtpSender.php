<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kstmostofa\LaravelWhatsApp\Facades\WhatsApp;
use RuntimeException;

class WhatsAppOtpSender
{
    public function sendOtp(User $user, string $pinCode): void
    {
        $token = (string) $this->getConfiguredValue('services.whatsapp.token', 'laravel-whatsapp.access_token');
        $phoneNumberId = (string) $this->getConfiguredValue('services.whatsapp.from-phone-number-id', 'laravel-whatsapp.phone_number_id');

        if ($token === '' || $phoneNumberId === '') {
            throw new RuntimeException('Meta WhatsApp credentials are missing. Please set WHATSAPP_TOKEN/WHATSAPP_ACCESS_TOKEN and WHATSAPP_FROM_PHONE_NUMBER_ID/WHATSAPP_PHONE_NUMBER_ID.');
        }

        $templateName = (string) config('services.whatsapp.otp_template_name', '');
        $templateLanguage = (string) config('services.whatsapp.otp_template_language', 'id');
        $useTemplateSetting = config('services.whatsapp.use_template');
        $useTemplate = true;

        if ($useTemplateSetting === null || $useTemplateSetting === '') {
            $useTemplate = true;
        } else {
            $useTemplate = filter_var($useTemplateSetting, FILTER_VALIDATE_BOOLEAN);
        }

        $messageTemplate = (string) config(
            'services.whatsapp.otp_message_template',
            'Kode PIN Anda: :otp. Berlaku :minutes menit.'
        );
        $expiresInMinutes = (string) config('services.whatsapp.otp_expired_in_minutes', 10);
        $appName = (string) config('app.name', 'FinBoard');

        $message = strtr($this->normalizeTemplate($messageTemplate), [
            ':app' => $appName,
            ':name' => $user->name,
            ':otp' => $pinCode,
            ':minutes' => $expiresInMinutes,
        ]);

        $recipient = $this->normalizeRecipient($user->whatsapp_number);

        try {
            if ($useTemplate && $templateName !== '') {
                try {
                    $components = $this->buildTemplateComponents($pinCode);
                    $response = WhatsApp::messages($phoneNumberId)->sendTemplate($recipient, $templateName, $templateLanguage, $components);

                    Log::info('Meta WhatsApp OTP request', [
                        'phone_number_id' => $phoneNumberId,
                        'recipient' => $recipient,
                        'payload_type' => 'template',
                        'template_name' => $templateName,
                        'status' => 200,
                        'body' => $response,
                    ]);

                    if (isset($response['messages'][0]['id'])) {
                        return;
                    }
                } catch (\Throwable $templateException) {
                    if ($this->shouldRetryTemplateWithUrlButton($templateException)) {
                        try {
                            $retryComponents = $this->buildTemplateComponentsWithOptions($pinCode, true);

                            Log::warning('Meta WhatsApp template send failed due to missing parameter, retrying with URL button component', [
                                'phone_number_id' => $phoneNumberId,
                                'recipient' => $recipient,
                                'template_name' => $templateName,
                                'message' => $templateException->getMessage(),
                            ]);

                            $retryResponse = WhatsApp::messages($phoneNumberId)->sendTemplate($recipient, $templateName, $templateLanguage, $retryComponents);

                            Log::info('Meta WhatsApp OTP request', [
                                'phone_number_id' => $phoneNumberId,
                                'recipient' => $recipient,
                                'payload_type' => 'template',
                                'template_name' => $templateName,
                                'status' => 200,
                                'body' => $retryResponse,
                                'retry_with_url_button' => true,
                            ]);

                            if (isset($retryResponse['messages'][0]['id'])) {
                                return;
                            }

                            throw new RuntimeException('Meta WhatsApp accepted template retry request but did not return a message ID.');
                        } catch (\Throwable $retryException) {
                            Log::warning('Meta WhatsApp template retry with URL button failed, falling back to plain text', [
                                'phone_number_id' => $phoneNumberId,
                                'recipient' => $recipient,
                                'template_name' => $templateName,
                                'message' => $retryException->getMessage(),
                            ]);

                            $templateException = $retryException;
                        }
                    }

                    Log::warning('Meta WhatsApp template send failed, falling back to plain text', [
                        'phone_number_id' => $phoneNumberId,
                        'recipient' => $recipient,
                        'template_name' => $templateName,
                        'message' => $templateException->getMessage(),
                    ]);
                }
            }

            $response = WhatsApp::messages($phoneNumberId)->sendText($recipient, $message, false);

            Log::info('Meta WhatsApp OTP request', [
                'phone_number_id' => $phoneNumberId,
                'recipient' => $recipient,
                'payload_type' => 'text',
                'template_name' => $templateName,
                'status' => 200,
                'body' => $response,
            ]);

            if (isset($response['messages'][0]['id'])) {
                return;
            }

            throw new RuntimeException('Meta WhatsApp accepted the request but did not return a message ID. The message may still not be delivered.');
        } catch (\Throwable $e) {
            Log::error('Meta WhatsApp OTP send failed', [
                'phone_number_id' => $phoneNumberId,
                'recipient' => $recipient,
                'template_name' => $templateName,
                'token_present' => $token !== '',
                'token_prefix' => $token !== '' ? substr($token, 0, 12) : '',
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Meta WhatsApp delivery failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function getConfiguredValue(string $serviceKey, string $packageKey): mixed
    {
        $value = config($serviceKey);

        if ($value === null || $value === '') {
            $value = config($packageKey);
        }

        if ($value === null || $value === '') {
            $envKey = $this->mapConfigKeyToEnvKey($serviceKey, $packageKey);
            $value = env($envKey);
        }

        return $value;
    }

    private function mapConfigKeyToEnvKey(string $serviceKey, string $packageKey): string
    {
        return match (true) {
            $packageKey === 'laravel-whatsapp.access_token' => 'WHATSAPP_ACCESS_TOKEN',
            $packageKey === 'laravel-whatsapp.phone_number_id' => 'WHATSAPP_PHONE_NUMBER_ID',
            $serviceKey === 'services.whatsapp.token' => 'WHATSAPP_TOKEN',
            $serviceKey === 'services.whatsapp.from-phone-number-id' => 'WHATSAPP_FROM_PHONE_NUMBER_ID',
            default => '',
        };
    }

    private function normalizeRecipient(?string $number): string
    {
        if (!$number) {
            throw new RuntimeException('Recipient WhatsApp number is missing.');
        }

        $normalized = preg_replace('/[^0-9]/', '', $number) ?? '';
        if ($normalized === '') {
            throw new RuntimeException('Recipient WhatsApp number is invalid.');
        }

        if (str_starts_with($normalized, '0')) {
            $normalized = '62' . substr($normalized, 1);
        }

        return $normalized;
    }

    private function normalizeTemplate(string $template): string
    {
        $template = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $template);
        $template = preg_replace("/\\\\\r?\n/", "\n", $template) ?? $template;
        $template = preg_replace('/[ \t]*\\$/m', '', $template) ?? $template;
        $template = str_replace(["\r\n", "\r"], "\n", $template);

        return trim($template);
    }

    private function buildTemplateComponents(string $pinCode): array
    {
        return $this->buildTemplateComponentsWithOptions($pinCode, false);
    }

    private function buildTemplateComponentsWithOptions(string $pinCode, bool $includeUrlButton): array
    {
        $components = [[
            'type' => 'body',
            'parameters' => [[
                'type' => 'text',
                'text' => (string) $pinCode,
            ]],
        ]];

        $buttonIndex = config('services.whatsapp.otp_template_url_button_index');
        $hasConfiguredButtonIndex = $buttonIndex !== null && $buttonIndex !== '';

        if ($includeUrlButton || $hasConfiguredButtonIndex) {
            $resolvedButtonIndex = $hasConfiguredButtonIndex ? (string) $buttonIndex : '0';
            $configuredUrlParameter = config('services.whatsapp.otp_template_url_button_parameter');
            $urlParameter = ($configuredUrlParameter === null || $configuredUrlParameter === '')
                ? $pinCode
                : (string) $configuredUrlParameter;

            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => $resolvedButtonIndex,
                'parameters' => [[
                    'type' => 'text',
                    'text' => $urlParameter,
                ]],
            ];
        }

        return $components;
    }

    private function shouldRetryTemplateWithUrlButton(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'required parameter is missing')
            || str_contains($message, '#131008')
            || str_contains($message, 'button at index')
            || str_contains($message, 'buttons:');
    }
}
