<?php

use Celestial\Exceptions\Services\Billing\NegativeBalanceLimitReachedException;
use Celestial\Services\Billing\BillingService;
use Celestial\Services\Payments\PaymentSession;

class BalanceTest extends Requests
{
    protected function tearDown()
    {
        Mockery::close();
    }

    /** @test */
    function exception_should_be_thrown_when_profile_has_negative_balance_limit_reached()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $profileRequest = $this->getProfileByIdRequest();
            $spendFeatureRequest = $this->getSpendFeaturePaymentRequriedRequest();

            $api->shouldReceive('request')
                ->with($profileRequest['method'], $profileRequest['url'])
                ->andReturn($profileRequest['response']);

            $api->shouldReceive('request')
                ->with($spendFeatureRequest['method'], $spendFeatureRequest['url'], $spendFeatureRequest['params'])
                ->andReturn($spendFeatureRequest['response']);
        });

        $service = new BillingService($api);
        $profile = $service->getProfileById(1);

        $this->expectException(NegativeBalanceLimitReachedException::class);
        $profile->spendFeature('first');
    }

    /** @test */
    function it_can_retrieve_balance_changes_history_for_profile()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $profileRequest = $this->getProfileByIdRequest();
            $historyRequest = $this->getBalanceChangesHistoryRequest();

            $api->shouldReceive('request')
                ->with($profileRequest['method'], $profileRequest['url'])
                ->andReturn($profileRequest['response']);

            $api->shouldReceive('request')
                ->with($historyRequest['method'], $historyRequest['url'], $historyRequest['params'])
                ->andReturn($historyRequest['response']);
        });

        $service = new BillingService($api);

        $profile = $service->getProfileById(1);

        $history = $profile->balanceHistory('Europe/Moscow');

        $this->assertEquals(2, count($history));

        $this->assertEquals('decrease', $history[0]['direction']);
        $this->assertEquals('plan.activation', $history[0]['type']);

        $this->assertEquals('increase', $history[1]['direction']);
        $this->assertEquals('deposit.dummy', $history[1]['type']);
    }

    /** @test */
    function it_should_notify_billing_about_payment_status_updates()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $profileRequest = $this->getProfileByIdRequest();
            $acceptedRequest = $this->getPaymentAcceptedRequest();
            $cancelledRequest = $this->getPaymentCancelledRequest();
            $refundedRequest = $this->getPaymentRefundedRequest();

            $api->shouldReceive('request')
                ->with($profileRequest['method'], $profileRequest['url'])
                ->andReturn($profileRequest['response']);

            $api->shouldReceive('request')
                ->with($acceptedRequest['method'], $acceptedRequest['url'], $acceptedRequest['params'])
                ->andReturn($acceptedRequest['response']);

            $api->shouldReceive('request')
                ->with($cancelledRequest['method'], $cancelledRequest['url'], $cancelledRequest['params'])
                ->andReturn($cancelledRequest['response']);

            $api->shouldReceive('request')
                ->with($refundedRequest['method'], $refundedRequest['url'], $refundedRequest['params'])
                ->andReturn($refundedRequest['response']);
        });

        $service = new BillingService($api);
        $profile = $service->getProfileById(1);
        $session = new PaymentSession([
            'uuid' => '5bee9f7e-c08f-11e6-a4a6-cec0c932ce01',
            'amount' => 10000,
            'provider' => 'dummy',
            'recurrent' => false,
        ]);

        $profile->paymentAccepted($session);
        $profile->paymentCancelled($session);
        $profile->paymentRefunded($session);
    }
}
