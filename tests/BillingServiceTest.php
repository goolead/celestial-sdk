<?php

use Celestial\Exceptions\Services\Billing\FeatureIsNotAvailableException;
use Celestial\Exceptions\Services\Billing\NegativeBalanceLimitReachedException;
use Celestial\Exceptions\Services\Billing\ProfileWasNotFoundException;
use Celestial\Services\Billing\BillingService;
use Celestial\Services\Payments\PaymentSession;
use Celestial\Services\Payments\PaymentsService;

class BillingServiceTest extends Requests
{
    protected function tearDown()
    {
        Mockery::close();
    }

    /** @test */
    function it_can_create_new_billing_profiles()
    {
        $request = $this->createProfileRequest();

        $api = ServicesTestsHelper::mockApi(function ($api) use ($request) {
            $api->shouldReceive('request')
                ->with($request['method'], $request['url'], $request['params'])
                ->andReturn($request['response']);
        });

        $service = new BillingService($api);

        $profile = $service->createProfile($request['params']['form_params']);

        $this->assertEquals(1, $profile->userId());
        $this->assertEquals('free', $profile->billingPlan());
        $this->assertEquals('monthly', $profile->billingPeriod());
        $this->assertEquals(0, $profile->rawBalance());
    }

    /** @test */
    function it_should_load_existed_profiles_by_profile_id()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getProfileByIdRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'])
                ->andReturn($request['response']);
        });

        $service = new BillingService($api);

        $profile = $service->getProfileById(1);

        $this->assertEquals(1, $profile->userId());
        $this->assertEquals('free', $profile->billingPlan());
        $this->assertEquals('monthly', $profile->billingPeriod());
        $this->assertEquals(0, $profile->rawBalance());
    }

    /** @test */
    function it_should_load_existed_profiles_by_user_id()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getProfileByUserIdRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'])
                ->andReturn($request['response']);
        });

        $service = new BillingService($api);

        $profile = $service->getProfileByUserId(1);

        $this->assertEquals(1, $profile->userId());
        $this->assertEquals('free', $profile->billingPlan());
        $this->assertEquals('monthly', $profile->billingPeriod());
        $this->assertEquals(0, $profile->rawBalance());
    }

    /** @test */
    function it_should_throw_exception_when_profile_was_not_found()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getWrongProfileByIdRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'])
                ->andReturn($request['response']);
        });

        $service = new BillingService($api);

        $this->expectException(ProfileWasNotFoundException::class);

        $profile = $service->getProfileById(2);
    }

    /** @test */
    function it_should_throw_exception_when_profile_was_not_found_by_user_id()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getWrongProfileByUserIdRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'])
                ->andReturn($request['response']);
        });

        $service = new BillingService($api);

        $this->expectException(ProfileWasNotFoundException::class);

        $profile = $service->getProfileByUserId(2);
    }
}
