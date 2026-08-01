<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\WhatsAppOtpSender;
use Kstmostofa\LaravelWhatsApp\Client\CloudClient;
use Kstmostofa\LaravelWhatsApp\Client\Resources\MessagesResource;
use Kstmostofa\LaravelWhatsApp\Facades\WhatsApp;
use Mockery;
use Tests\TestCase;

class WhatsAppOtpSenderTest extends TestCase
{
    public function test_it_sends_pin_via_meta_whatsapp_template_when_configured(): void
    {
        config()->set('services.whatsapp.otp_template_name', 'finboard_otp');
        config()->set('services.whatsapp.otp_template_language', 'id');
        config()->set('services.whatsapp.otp_message_template', 'Kode PIN Anda: :otp');
        config()->set('services.whatsapp.otp_expired_in_minutes', 10);
        config()->set('services.whatsapp.use_template', true);

        $messages = Mockery::mock(MessagesResource::class, [Mockery::mock(CloudClient::class), '123456789']);
        $messages->shouldReceive('sendTemplate')
            ->once()
            ->with('628123456789', 'finboard_otp', 'id', [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => '654321'],
                ],
            ]])
            ->andReturn(['messages' => [['id' => 'msg_123']]]);

        WhatsApp::shouldReceive('messages')
            ->once()
            ->andReturn($messages);

        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'whatsapp_number' => '628123456789',
        ]);

        $sender = new WhatsAppOtpSender();
        $sender->sendOtp($user, '654321');
    }

    public function test_it_falls_back_to_plain_text_when_template_is_disabled(): void
    {
        config()->set('services.whatsapp.otp_template_name', 'finboard_otp');
        config()->set('services.whatsapp.otp_template_language', 'id');
        config()->set('services.whatsapp.otp_message_template', 'Kode PIN Anda: :otp');
        config()->set('services.whatsapp.otp_expired_in_minutes', 10);
        config()->set('services.whatsapp.use_template', false);

        $messages = Mockery::mock(MessagesResource::class, [Mockery::mock(CloudClient::class), '123456789']);
        $messages->shouldReceive('sendText')
            ->once()
            ->with('628123456789', 'Kode PIN Anda: 654321', false)
            ->andReturn(['messages' => [['id' => 'msg_456']]]);

        WhatsApp::shouldReceive('messages')
            ->once()
            ->andReturn($messages);

        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'whatsapp_number' => '628123456789',
        ]);

        $sender = new WhatsAppOtpSender();
        $sender->sendOtp($user, '654321');
    }

    public function test_it_uses_the_package_config_when_service_config_is_empty(): void
    {
        config()->set('services.whatsapp.otp_template_name', 'finboard_otp');
        config()->set('services.whatsapp.otp_template_language', 'id');
        config()->set('services.whatsapp.use_template', true);
        config()->set('services.whatsapp.token', null);
        config()->set('services.whatsapp.from-phone-number-id', null);
        config()->set('laravel-whatsapp.access_token', 'package-token');
        config()->set('laravel-whatsapp.phone_number_id', 'package-phone-number-id');

        $messages = Mockery::mock(MessagesResource::class, [Mockery::mock(CloudClient::class), '123456789']);
        $messages->shouldReceive('sendTemplate')
            ->once()
            ->with('628123456789', 'finboard_otp', 'id', [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => '654321'],
                ],
            ]])
            ->andReturn(['messages' => [['id' => 'msg_789']]]);

        WhatsApp::shouldReceive('messages')
            ->once()
            ->andReturn($messages);

        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'whatsapp_number' => '628123456789',
        ]);

        $sender = new WhatsAppOtpSender();
        $sender->sendOtp($user, '654321');
    }

    public function test_it_uses_package_style_environment_aliases_when_service_values_are_empty(): void
    {
        config()->set('services.whatsapp.otp_template_name', 'finboard_otp');
        config()->set('services.whatsapp.otp_template_language', 'id');
        config()->set('services.whatsapp.use_template', true);
        config()->set('services.whatsapp.token', null);
        config()->set('services.whatsapp.from-phone-number-id', null);
        config()->set('laravel-whatsapp.access_token', null);
        config()->set('laravel-whatsapp.phone_number_id', null);

        putenv('WHATSAPP_ACCESS_TOKEN=env-token');
        putenv('WHATSAPP_PHONE_NUMBER_ID=env-phone-id');

        $messages = Mockery::mock(MessagesResource::class, [Mockery::mock(CloudClient::class), '123456789']);
        $messages->shouldReceive('sendTemplate')
            ->once()
            ->with('628123456789', 'finboard_otp', 'id', [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => '654321'],
                ],
            ]])
            ->andReturn(['messages' => [['id' => 'msg_999']]]);

        WhatsApp::shouldReceive('messages')
            ->once()
            ->andReturn($messages);

        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'whatsapp_number' => '628123456789',
        ]);

        $sender = new WhatsAppOtpSender();
        $sender->sendOtp($user, '654321');

        putenv('WHATSAPP_ACCESS_TOKEN');
        putenv('WHATSAPP_PHONE_NUMBER_ID');
    }

    public function test_package_config_uses_existing_whatsapp_env_aliases(): void
    {
        putenv('WHATSAPP_TOKEN=env-service-token');
        putenv('WHATSAPP_FROM_PHONE_NUMBER_ID=env-service-phone-id');

        $config = require base_path('config/laravel-whatsapp.php');

        $this->assertSame(env('WHATSAPP_TOKEN'), $config['access_token']);
        $this->assertSame(env('WHATSAPP_FROM_PHONE_NUMBER_ID'), $config['phone_number_id']);

        putenv('WHATSAPP_TOKEN');
        putenv('WHATSAPP_FROM_PHONE_NUMBER_ID');
    }

    public function test_it_falls_back_to_plain_text_when_template_send_fails(): void
    {
        config()->set('services.whatsapp.otp_template_name', 'finboard_otp');
        config()->set('services.whatsapp.otp_template_language', 'id');
        config()->set('services.whatsapp.otp_message_template', 'Kode PIN Anda: :otp');
        config()->set('services.whatsapp.otp_expired_in_minutes', 10);
        config()->set('services.whatsapp.use_template', true);

        $messages = Mockery::mock(MessagesResource::class, [Mockery::mock(CloudClient::class), '123456789']);
        $messages->shouldReceive('sendTemplate')
            ->twice()
            ->andThrow(new \RuntimeException('#131008 Required parameter is missing'));
        $messages->shouldReceive('sendText')
            ->once()
            ->with('628123456789', 'Kode PIN Anda: 654321', false)
            ->andReturn(['messages' => [['id' => 'msg_fallback']]]);

        WhatsApp::shouldReceive('messages')
            ->times(3)
            ->andReturn($messages);

        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'whatsapp_number' => '628123456789',
        ]);

        $sender = new WhatsAppOtpSender();
        $sender->sendOtp($user, '654321');
    }

    public function test_it_retries_template_with_url_button_component_when_required_parameter_missing(): void
    {
        config()->set('services.whatsapp.otp_template_name', 'finboard_otp');
        config()->set('services.whatsapp.otp_template_language', 'id');
        config()->set('services.whatsapp.otp_message_template', 'Kode PIN Anda: :otp');
        config()->set('services.whatsapp.otp_expired_in_minutes', 10);
        config()->set('services.whatsapp.use_template', true);

        $messages = Mockery::mock(MessagesResource::class, [Mockery::mock(CloudClient::class), '123456789']);
        $messages->shouldReceive('sendTemplate')
            ->once()
            ->with('628123456789', 'finboard_otp', 'id', [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => '654321'],
                ],
            ]])
            ->andThrow(new \RuntimeException('#131008 Required parameter is missing'));

        $messages->shouldReceive('sendTemplate')
            ->once()
            ->with('628123456789', 'finboard_otp', 'id', [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => '654321'],
                ],
            ], [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => '654321'],
                ],
            ]])
            ->andReturn(['messages' => [['id' => 'msg_template_retry_ok']]]);

        $messages->shouldNotReceive('sendText');

        WhatsApp::shouldReceive('messages')
            ->twice()
            ->andReturn($messages);

        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'whatsapp_number' => '628123456789',
        ]);

        $sender = new WhatsAppOtpSender();
        $sender->sendOtp($user, '654321');
    }
}
