<?php

use Celestial\Exceptions\Services\Billing\FeatureIsNotAvailableException;
use Celestial\Exceptions\Services\Billing\NegativeBalanceLimitReachedException;
use Celestial\Exceptions\Services\Billing\ProfileWasNotFoundException;
use Celestial\Services\Billing\BillingService;
use Celestial\Services\Payments\PaymentSession;
use Celestial\Services\Payments\PaymentsService;

class BillingServiceTest extends PHPUnit_Framework_TestCase
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
        $request = $this->getProfileByIdRequest();

        $api = ServicesTestsHelper::mockApi(function ($api) use ($request) {
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
        $request = $this->getProfileByUserIdRequest();

        $api = ServicesTestsHelper::mockApi(function ($api) use ($request) {
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
        $request = $this->getWrongProfileByIdRequest();

        $api = ServicesTestsHelper::mockApi(function ($api) use ($request) {
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

    protected function getPaymentAcceptedRequest()
    {
        return [
            'method' => 'POST',
            'url' => '/users/1/payments',
            'params' => [
                'form_params' => [
                    'amount' => 10000,
                    'transaction' => '5bee9f7e-c08f-11e6-a4a6-cec0c932ce01',
                    'system' => 'dummy',
                    'meta' => [
                        'recurrent' => 0,
                    ],
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => [],
            ]),
        ];
    }

    protected function getPaymentCancelledRequest()
    {
        return [
            'method' => 'DELETE',
            'url' => '/users/1/payments',
            'params' => [
                'form_params' => [
                    'amount' => 10000,
                    'type' => 'payments.cancel',
                    'meta' => [
                        'transaction' => '5bee9f7e-c08f-11e6-a4a6-cec0c932ce01',
                    ],
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => [],
            ]),
        ];
    }

    protected function getPaymentRefundedRequest()
    {
        return [
            'method' => 'DELETE',
            'url' => '/users/1/payments',
            'params' => [
                'form_params' => [
                    'amount' => 10000,
                    'type' => 'payments.refund',
                    'meta' => [
                        'transaction' => '5bee9f7e-c08f-11e6-a4a6-cec0c932ce01',
                    ],
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => [],
            ]),
        ];
    }

    protected function getPlansRequest()
    {
        return [
            'method' => 'GET',
            'url' => '/plans',
            'params' => [],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'free',
                        'periods' => [
                            'monthly' => [
                                [
                                    'region' => 'ru',
                                    'period' => 'monthly',
                                    'price' => [
                                        'raw' => 0,
                                        'formatted' => '0 руб.',
                                    ],
                                ],
                            ],
                        ],
                        'features' => [
                            'first' => [
                                'id' => 1,
                                'name' => 'first',
                                'limit' => 0,
                                'unlimited' => 0,
                                'excess_price' => [
                                    'raw' => 2000,
                                    'formatted' => '20 руб.',
                                ],
                            ],
                            'second' => [
                                'id' => 1,
                                'name' => 'first',
                                'limit' => -1,
                                'unlimited' => 1,
                                'excess_price' => [
                                    'raw' => 0,
                                    'formatted' => '0 руб.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 2,
                        'name' => 'other',
                        'periods' => [
                            'monthly' => [
                                [
                                    'region' => 'ru',
                                    'period' => 'monthly',
                                    'price' => [
                                        'raw' => 150000,
                                        'formatted' => '1500 руб.',
                                    ],
                                ],
                            ],
                        ],
                        'features' => [
                            'first' => [
                                'id' => 1,
                                'name' => 'first',
                                'limit' => 10,
                                'unlimited' => 0,
                                'excess_price' => [
                                    'raw' => 1500,
                                    'formatted' => '15 руб.',
                                ],
                            ],
                            'second' => [
                                'id' => 1,
                                'name' => 'second',
                                'limit' => -1,
                                'unlimited' => 1,
                                'excess_price' => [
                                    'raw' => 0,
                                    'formatted' => '0 руб.',
                                ],
                            ],
                        ],
                    ]
                ],
            ]),
        ];
    }

    protected function paymentsMock($callback = null)
    {
        $api = ServicesTestsHelper::mockApi($callback);

        return new PaymentsService($api);
    }

    protected function createProfileRequest()
    {
        return [
            'method' => 'POST',
            'url' => '/profiles',
            'params' => [
                'form_params' => [
                    'user_id' => 1,
                    'region' => 'ru',
                    'balance' => 0,
                    'plan' => 'simple',
                    'period' => 'monthly',
                    'trial' => 0,
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse($this->profileData()),
        ];
    }

    protected function getProfileByIdRequest(array $profileParams = [])
    {
        return [
            'method' => 'GET',
            'url' => '/profiles/1',
            'response' => ServicesTestsHelper::toApiResponse($this->profileData($profileParams)),
        ];
    }

    protected function getProfileByUserIdRequest(array $profileParams = [])
    {
        return [
            'method' => 'GET',
            'url' => '/users/1',
            'response' => ServicesTestsHelper::toApiResponse($this->profileData($profileParams)),
        ];
    }

    protected function getWrongProfileByIdRequest()
    {
        return [
            'method' => 'GET',
            'url' => '/profiles/2',
            'response' => ServicesTestsHelper::toApiResponse([
                'error' => 404,
            ], 404),
        ];
    }

    protected function getWrongProfileByUserIdRequest()
    {
        return [
            'method' => 'GET',
            'url' => '/users/2',
            'response' => ServicesTestsHelper::toApiResponse([
                'error' => 404,
            ], 404),
        ];
    }

    protected function getSubscriptionRequest()
    {
        return [
            'method' => 'PUT',
            'url' => '/profiles/1/subscription',
            'params' => [
                'form_params' => [
                    'plan' => 'other',
                    'period' => 'monthly',
                    'trial' => 0,
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => $this->subscriptionData([
                    'plan_id' => 2,
                    'plan_name' => 'other',
                    'plan_price' => 150000,
                ])
            ]),
        ];
    }

    protected function getInsufficientFundsSubscriptionRequest()
    {
        return [
            'method' => 'PUT',
            'url' => '/profiles/1/subscription',
            'params' => [
                'form_params' => [
                    'plan' => 'other',
                    'period' => 'monthly',
                    'trial' => 0,
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'error' => 'Insufficient balance.',
                'data' => [
                    'currency' => 'rub',
                    'current' => 0,
                    'required' => 150000,
                    'required_formatted' => '1500 руб.',
                ],
            ], 402),
        ];
    }

    protected function getPaymentSessionInitRequest()
    {
        return [
            'method' => 'POST',
            'url' => '/payments/init',
            'params' => [
                'form_params' => [
                    'provider' => 'dummy',
                    'amount' => 150000,
                    'currency' => 'rub',
                    'user_id' => 1,
                    'email' => 'john@example.org',
                    'recurrent' => 0,
                    'notifications' => [
                        [
                            'type' => 'confirmed',
                            'service' => 'billing',
                            'service_token' => 'api-token',
                            'url' => 'https://example.org/profiles/1/subscription',
                            'method' => 'PUT',
                            'form' => [
                                'plan' => 'other',
                                'period' => 'monthly',
                                'trial' => 0,
                            ],
                        ],
                    ],
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

    protected function profileData(array $profileParams = [])
    {
        $balance = intval($profileParams['balance'] ?? 0);

        return [
            'success' => 1,
            'data' => [
                'id' => 1,
                'user_id' => 1,
                'region' => 'ru',
                'currency' => 'rub',
                'balance' => [
                    'raw' => $balance,
                    'formatted' => ($balance / 100).' руб.',
                ],
                'subscription' => $this->subscriptionData(),
                'created_at' => [
                    'date' => '2017-01-01 00:00:00.000000',
                    'timezone_type' => 3,
                    'timezone' => 'UTC',
                ],
            ],
        ];
    }

    protected function subscriptionData(array $subscriptionParams = [])
    {
        $planName = $subscriptionParams['plan_name'] ?? 'free';
        $planId = intval($subscriptionParams['plan_id'] ?? 1);
        $planPeriod = $subscriptionParams['plan_period'] ?? 'monthly';
        $isTrial = intval($subscriptionParams['trial'] ?? 0);
        $planPrice = intval($subscriptionParams['plan_price'] ?? 0);

        return [
            'id' => 1,
            'profile_id' => 1,
            'plan_id' => $planId,
            'period' => $planPeriod,
            'is_trial' => $isTrial,
            'is_grace' => 0,
            'ends_at' => [
                'date' => '2019-01-01 00:00:00.000000',
                'timezone_type' => 3,
                'timezone' => 'UTC',
            ],
            'grace' => [
                'till' => null,
                'plan_id' => 0,
            ],
            'renewed_at' => null,
            'plan' => [
                'id' => $planId,
                'name' => $planName,
                'periods' => [
                    'monthly' => [
                        [
                            'region' => 'ru',
                            'period' => 'monthly',
                            'price' => [
                                'raw' => $planPrice,
                                'formatted' => ($planPrice / 100).' руб.',
                            ],
                        ],
                    ],
                ],
                'features' => [
                    'first' => [
                        'id' => 1,
                        'name' => 'first',
                        'limit' => 0,
                        'unlimited' => 0,
                        'excess_price' => [
                            'raw' => 2000,
                            'formatted' => '20 руб.',
                        ],
                    ],
                    'second' => [
                        'id' => 1,
                        'name' => 'second',
                        'limit' => -1,
                        'unlimited' => 1,
                        'excess_price' => [
                            'raw' => 0,
                            'formatted' => '0 руб.',
                        ],
                    ],
                ],
            ],
            'features' => [
                'first' => [
                    'id' => 1,
                    'name' => 'first',
                    'limit' => 0,
                    'unlimited' => 0,
                    'left' => 0,
                    'can_use' => 0,
                ],
                'second' => [
                    'id' => 1,
                    'name' => 'second',
                    'limit' => -1,
                    'unlimited' => 1,
                    'left' => -1,
                    'can_use' => 1,
                ],
            ],
        ];
    }

    public function getSpendFeatureRequest()
    {
        return [
            'method' => 'DELETE',
            'url' => '/profiles/1/subscription/features',
            'params' => [
                'form_params' => [
                    'feature' => 'first',
                    'value' => 1,
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => [
                    'feature' => [
                        'id' => 1,
                        'name' => 'first',
                        'limit' => 0,
                        'unlimited' => 0,
                        'left' => 0,
                        'can_use' => 0,
                    ],
                    'profile' => $this->profileData(['balance' => -2000])['data'],
                ],
            ]),
        ];
    }

    public function getSpendFeaturePaymentRequriedRequest()
    {
        return [
            'method' => 'DELETE',
            'url' => '/profiles/1/subscription/features',
            'params' => [
                'form_params' => [
                    'feature' => 'first',
                    'value' => 1,
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'error' => 402,
                'message' => 'You can not use feature "first" due to subscription limit and negative balance.',
            ], 402),
        ];
    }

    public function getSpendFeatureForbiddenRequest()
    {
        return [
            'method' => 'DELETE',
            'url' => '/profiles/1/subscription/features',
            'params' => [
                'form_params' => [
                    'feature' => 'first',
                    'value' => 1,
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'error' => 403,
                'message' => 'Your plan has no access to feature "first".',
            ], 403),
        ];
    }

    public function getBalanceChangesHistoryRequest()
    {
        return [
            'method' => 'GET',
            'url' => '/profiles/1/balance/history',
            'params' => [
                'query' => [
                    'timezone' => 'Europe/Moscow',
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'pagination' => [
                    'total' => 2,
                    'per_page' => 15,
                    'current_page' => 1,
                    'last_page' => 1,
                    'has_more_pages' => 0,
                ],
                'data' => [
                    [
                        'id' => 1,
                        'profile_id' => 1,
                        'direction' => 'decrease',
                        'type' => 'plan.activation',
                        'transaction_id' => 'I8C7GxkKKvxXihQmIEAGfDkxaVhcplRH',
                        'currency' => 'rub',
                        'amount' => [
                            'raw' => 150000,
                            'formatted' => '1500 руб.',
                        ],
                        'balance_before' => [
                            'raw' => 150000,
                            'formatted' => '1500 руб.',
                        ],
                        'balance_after' => [
                            'raw' => 0,
                            'formatted' => '0 руб.',
                        ],
                        'meta_data' => [
                            'plan' => 'other',
                        ],
                        'created_at' => '2017-01-01 00:01:00',
                        'updated_at' => '2017-01-01 00:01:00',
                    ],
                    [
                        'id' => 1,
                        'profile_id' => 1,
                        'direction' => 'increase',
                        'type' => 'deposit.dummy',
                        'transaction_id' => 'FI8C7GxkKKvxXihQIEAGfDkxaVhcplRH',
                        'currency' => 'rub',
                        'amount' => [
                            'raw' => 150000,
                            'formatted' => '1500 руб.',
                        ],
                        'balance_before' => [
                            'raw' => 0,
                            'formatted' => '0 руб.',
                        ],
                        'balance_after' => [
                            'raw' => 150000,
                            'formatted' => '1500 руб.',
                        ],
                        'meta_data' => [],
                        'created_at' => '2017-01-01 00:00:00',
                        'updated_at' => '2017-01-01 00:00:00',
                    ],
                ],
            ]),
        ];
    }
}
