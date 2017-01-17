<?php

use Celestial\Exceptions\Services\Billing\FeatureIsNotAvailableException;
use Celestial\Services\Billing\BillingService;

class FeaturesTest extends Requests
{
    protected function tearDown()
    {
        Mockery::close();
    }

    /** @test */
    function it_should_correctly_work_with_feature_limits()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getProfileByIdRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'])
                ->andReturn($request['response']);
        });

        $service = new BillingService($api);

        $profile = $service->getProfileById(1);

        $this->assertFalse($profile->isOnTrial());
        $this->assertFalse($profile->isOnGrace());

        $subscription = $profile->getSubscription();

        $this->assertEquals(5, $subscription->featureValue('third'));

        $this->assertTrue($subscription->hasFeature('first'));
        $this->assertTrue($subscription->hasFeature('second'));

        $this->assertFalse($subscription->canUseFeature('first'));
        $this->assertTrue($subscription->canUseFeature('second'));

        $profile->setRawBalance(50000);
        $useBalance = true;

        $this->assertFalse($subscription->canUseFeature('first'));
        $this->assertTrue($subscription->canUseFeature('first', $useBalance));
    }

    /** @test */
    function profile_can_spend_features()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $profileRequest = $this->getProfileByIdRequest();
            $spendFeatureRequest = $this->getSpendFeatureRequest();

            $api->shouldReceive('request')
                ->with($profileRequest['method'], $profileRequest['url'])
                ->andReturn($profileRequest['response']);

            $api->shouldReceive('request')
                ->with($spendFeatureRequest['method'], $spendFeatureRequest['url'], $spendFeatureRequest['params'])
                ->andReturn($spendFeatureRequest['response']);
        });

        $service = new BillingService($api);
        $profile = $service->getProfileById(1);

        $this->assertEquals(0, $profile->rawBalance());

        $profile->spendFeature('first');

        $this->assertEquals(-2000, $profile->rawBalance());
    }

    /** @test */
    function exception_should_be_thrown_when_profile_plan_has_no_access_to_feature()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $profileRequest = $this->getProfileByIdRequest();
            $spendFeatureRequest = $this->getSpendFeatureForbiddenRequest();

            $api->shouldReceive('request')
                ->with($profileRequest['method'], $profileRequest['url'])
                ->andReturn($profileRequest['response']);

            $api->shouldReceive('request')
                ->with($spendFeatureRequest['method'], $spendFeatureRequest['url'], $spendFeatureRequest['params'])
                ->andReturn($spendFeatureRequest['response']);
        });

        $service = new BillingService($api);
        $profile = $service->getProfileById(1);

        $this->expectException(FeatureIsNotAvailableException::class);
        $profile->spendFeature('first');
    }
}
