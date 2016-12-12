<?php

use Celestial\Exceptions\Services\Payments\UnableToInitializePaymentSessionException;
use Celestial\Exceptions\Services\Webhooks\UnableToCreateWebhookException;
use Celestial\Services\Payments\PaymentsService;

class PaymentsServiceTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    function it_can_initialize_payment_sessions()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getPaymentSessionInitRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'], $request['params'])
                ->andReturn($request['response']);
        });

        $payments = new PaymentsService($api);

        $user = [
            'id' => 1,
            'email' => 'john@example.org',
            'currency' => 'rub',
        ];
        $amount = 10000;
        $isRecurrent = false;
        $provider = 'dummy';
        $notifications = [];

        $session = $payments->initSession($user, $amount, $provider, $isRecurrent, $notifications);

        $this->assertEquals('https://example.org/payment-form', $session->paymentUrl());
    }

    /** @test */
    function it_should_throw_exception_when_session_data_is_missing()
    {
        $api = ServicesTestsHelper::mockApi();
        $payments = new PaymentsService($api);

        $user = [
            'id' => 1,
            'email' => 'john@example.org',
        ];
        $amount = 10000;
        $isRecurrent = false;
        $provider = 'dummy';
        $notifications = [];

        $this->expectException(InvalidArgumentException::class);
        $payments->initSession($user, $amount, $provider, $isRecurrent, $notifications);
    }

    /** @test */
    function it_should_throw_exception_when_api_request_fails_becase_of_invalid_provider()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getPaymentSessionInitFailedInvalidProviderRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'], $request['params'])
                ->andReturn($request['response']);
        });

        $payments = new PaymentsService($api);

        $user = [
            'id' => 1,
            'email' => 'john@example.org',
            'currency' => 'rub',
        ];
        $amount = 10000;
        $isRecurrent = false;
        $provider = 'unknown';
        $notifications = [];

        $this->expectException(UnableToInitializePaymentSessionException::class);
        $payments->initSession($user, $amount, $provider, $isRecurrent, $notifications);
    }

    /** @test */
    function it_should_create_webhooks_for_selected_user_payments()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getCreateWebhookRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'], $request['params'])
                ->andReturn($request['response']);
        });

        $payments = new PaymentsService($api);
        $actorType = 'user';
        $actorId = 1;
        $event = 'payment';
        $webhookUrl = 'https://example.org/webhook';

        $webhook = $payments->createWebhook($actorType, $actorId, $event, $webhookUrl);

        $this->assertEquals($webhookUrl, $webhook->url());
        $this->assertEquals($actorType, $webhook->actorType());
        $this->assertEquals($actorId, $webhook->actorId());
    }

    /** @test */
    function it_should_throw_exception_when_webhook_api_request_fails()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getCreateWebhookFailedRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'], $request['params'])
                ->andReturn($request['response']);
        });

        $payments = new PaymentsService($api);
        $actorType = 'unknown-actor';
        $actorId = 1;
        $event = 'payment';
        $webhookUrl = 'https://example.org/webhook';

        $this->expectException(UnableToCreateWebhookException::class);
        $payments->createWebhook($actorType, $actorId, $event, $webhookUrl);
    }

    protected function getPaymentSessionInitRequest()
    {
        return [
            'method' => 'POST',
            'url' => '/payments/init',
            'params' => [
                'form_params' => [
                    'provider' => 'dummy',
                    'amount' => 10000,
                    'currency' => 'rub',
                    'user_id' => 1,
                    'email' => 'john@example.org',
                    'recurrent' => 0,
                    'notifications' => [],
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => [
                    'response' => [
                        'payment_url' => 'https://example.org/payment-form',
                    ],
                ],
            ]),
        ];
    }

    protected function getPaymentSessionInitFailedInvalidProviderRequest()
    {
        return [
            'method' => 'POST',
            'url' => '/payments/init',
            'params' => [
                'form_params' => [
                    'provider' => 'unknown',
                    'amount' => 10000,
                    'currency' => 'rub',
                    'user_id' => 1,
                    'email' => 'john@example.org',
                    'recurrent' => 0,
                    'notifications' => [],
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'error' => 101,
                'message' => 'Provider "unknown" is invalid.',
            ], 422),
        ];
    }

    protected function getCreateWebhookRequest()
    {
        return [
            'method' => 'POST',
            'url' => '/webhooks',
            'params' => [
                'form_params' => [
                    'actor_type' => 'user',
                    'actor_id' => 1,
                    'event' => 'payment',
                    'url' => 'https://example.org/webhook',
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => [
                    'id' => 1,
                    'api_consumer_id' => 1,
                    'actor_type' => 'user',
                    'actor_id' => 1,
                    'event' => 'payment',
                    'url' => 'https://example.org/webhook',
                ],
            ]),
        ];
    }

    protected function getCreateWebhookFailedRequest()
    {
        return [
            'method' => 'POST',
            'url' => '/webhooks',
            'params' => [
                'form_params' => [
                    'actor_type' => 'unknown-actor',
                    'actor_id' => 1,
                    'event' => 'payment',
                    'url' => 'https://example.org/webhook',
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'actor_type' => ['The selected actor type is invalid.'],
            ], 422),
        ];
    }
}
