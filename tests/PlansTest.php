<?php

use Celestial\Services\Billing\BillingService;

class PlansTest extends Requests
{
    protected function tearDown()
    {
        Mockery::close();
    }

    /** @test */
    function it_should_load_plans_data()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getPlansRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'])
                ->andReturn($request['response']);
        });

        $service = new BillingService($api);

        $plans = $service->plans('ru');

        $this->assertEquals(2, count($plans));

        $this->assertEquals('free', $plans[0]['name']);
        $this->assertEquals(0, $plans[0]['prices']['monthly']['price']['raw']);

        $this->assertEquals('other', $plans[1]['name']);
        $this->assertEquals(150000, $plans[1]['prices']['monthly']['price']['raw']);
    }

    /** @test */
    function profile_can_subscribe_to_new_plan()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $profileRequest = $this->getProfileByIdRequest(['balance' => 150000]);
            $subscriptionRequest = $this->getSubscriptionRequest();

            $api->shouldReceive('request')
                ->with($profileRequest['method'], $profileRequest['url'])
                ->andReturn($profileRequest['response']);

            $api->shouldReceive('request')
                ->with($subscriptionRequest['method'], $subscriptionRequest['url'], $subscriptionRequest['params'])
                ->andReturn($subscriptionRequest['response']);
        });

        $service = new BillingService($api);

        $profile = $service->getProfileById(1);

        $this->assertEquals('free', $profile->billingPlan());

        $payments = $this->paymentsMock();

        $profile->subscribe($payments, 'john@example.org', 'other', 'monthly');

        $this->assertEquals('other', $profile->billingPlan());
    }

    /** @test */
    function profile_can_cancel_subscription()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $profileRequest = $this->getProfileByIdRequest(['balance' => 150000]);
            $subscriptionRequest = $this->getSubscriptionRequest();
            $cancellationRequest = $this->getSubscriptionCancellationRequest();

            $api->shouldReceive('request')
                ->with($profileRequest['method'], $profileRequest['url'])
                ->andReturn($profileRequest['response']);

            $api->shouldReceive('request')
                ->with($subscriptionRequest['method'], $subscriptionRequest['url'], $subscriptionRequest['params'])
                ->andReturn($subscriptionRequest['response']);

            $api->shouldReceive('request')
                ->with($cancellationRequest['method'], $cancellationRequest['url'])
                ->andReturn($cancellationRequest['response']);
        });

        $service = new BillingService($api);
        $profile = $service->getProfileById(1);

        $payments = $this->paymentsMock();
        $profile->subscribe($payments, 'john@example.org', 'other', 'monthly');

        $this->assertTrue($profile->hasSubscription());
        $this->assertTrue($profile->cancelSubscription());
        $this->assertFalse($profile->hasSubscription());
    }

    /** @test */
    function profile_can_subscribe_to_new_plan_with_explicit_ending_date()
    {
        $planEndsAt = '2030-01-01 00:00:00';

        $api = ServicesTestsHelper::mockApi(function ($api) use ($planEndsAt) {
            $profileRequest = $this->getProfileByIdRequest(['balance' => 150000]);
            $subscriptionRequest = $this->getSubscriptionRequest($planEndsAt);

            $api->shouldReceive('request')
                ->with($profileRequest['method'], $profileRequest['url'])
                ->andReturn($profileRequest['response']);

            $api->shouldReceive('request')
                ->with($subscriptionRequest['method'], $subscriptionRequest['url'], $subscriptionRequest['params'])
                ->andReturn($subscriptionRequest['response']);
        });

        $service = new BillingService($api);

        $profile = $service->getProfileById(1);

        $this->assertEquals('free', $profile->billingPlan());

        $payments = $this->paymentsMock();

        $profile->subscribe($payments, 'john@example.org', 'other', 'monthly', false, $planEndsAt);

        $this->assertEquals('other', $profile->billingPlan());
        $this->assertEquals($planEndsAt, $profile->getSubscription()->endsAtRaw());
    }

    /** @test */
    function payment_session_should_be_initialized_when_trying_to_update_subscription_with_insufficient_balance()
    {
        $api = ServicesTestsHelper::mockApi(['request', 'token'], function ($api) {
            $profileRequest = $this->getProfileByIdRequest();
            $subscriptionRequest = $this->getInsufficientFundsSubscriptionRequest();

            $api->shouldReceive('token')
                ->andReturn('api-token');

            $api->shouldReceive('request')
                ->with($profileRequest['method'], $profileRequest['url'])
                ->andReturn($profileRequest['response']);

            $api->shouldReceive('request')
                ->with($subscriptionRequest['method'], $subscriptionRequest['url'], $subscriptionRequest['params'])
                ->andReturn($subscriptionRequest['response']);
        });

        $service = new BillingService($api);

        $profile = $service->getProfileById(1);

        $this->assertEquals('free', $profile->billingPlan());

        $payments = $this->paymentsMock(function ($api) {
            $paymentSessionRequest = $this->getPaymentSessionInitRequest();

            $api->shouldReceive('request')
                ->with($paymentSessionRequest['method'], $paymentSessionRequest['url'], $paymentSessionRequest['params'])
                ->andReturn($paymentSessionRequest['response']);
        });

        $payments->setDefaultProvider('dummy');

        $subscriptionResult = $profile->subscribe($payments, 'john@example.org', 'other', 'monthly');

        $this->assertFalse($subscriptionResult->subscriptionUpdated());
        $this->assertTrue($subscriptionResult->paymentRequired());
        $this->assertEquals(150000, $subscriptionResult->requiredAmount());
        $this->assertEquals('1500 руб.', $subscriptionResult->requiredAmountFormatted());
        $this->assertEquals('https://example.org/payment-form', $subscriptionResult->paymentUrl());
    }
}
