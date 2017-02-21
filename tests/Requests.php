<?php

use Celestial\Services\Payments\PaymentsService;

class Requests extends PHPUnit_Framework_TestCase
{
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
                    'ends_at' => null,
                    'discount' => null,
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

    protected function getSubscriptionRequest($endsAt = null)
    {
        return [
            'method' => 'PUT',
            'url' => '/profiles/1/subscription',
            'params' => [
                'form_params' => [
                    'plan' => 'other',
                    'period' => 'monthly',
                    'trial' => 0,
                    'ends_at' => $endsAt,
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => $this->subscriptionData([
                    'plan_id' => 2,
                    'plan_name' => 'other',
                    'plan_price' => 150000,
                    'ends_at_raw' => $endsAt,
                ])
            ]),
        ];
    }

    protected function getSubscriptionCancellationRequest()
    {
        return [
            'method' => 'DELETE',
            'url' => '/profiles/1/subscription',
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
            ]),
        ];
    }

    protected function getInsufficientFundsSubscriptionRequest($endsAt = null)
    {
        return [
            'method' => 'PUT',
            'url' => '/profiles/1/subscription',
            'params' => [
                'form_params' => [
                    'plan' => 'other',
                    'period' => 'monthly',
                    'trial' => 0,
                    'ends_at' => $endsAt,
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

    protected function getPaymentSessionInitRequest($endsAt = null)
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
                                'ends_at' => $endsAt,
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
        $subscription = $profileParams['subscription'] ?? [];

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
                'subscription' => $this->subscriptionData($subscription),
                'created_at' => [
                    'date' => '2030-01-01 00:00:00.000000',
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
        $isGrace = intval($subscriptionParams['grace'] ?? 0);
        $isExpired = intval($subscriptionParams['expired'] ?? 0);
        $planPrice = intval($subscriptionParams['plan_price'] ?? 0);
        $endsAtRaw = $subscriptionParams['ends_at_raw'] ?? '2030-01-01 00:00:00';

        return [
            'id' => 1,
            'profile_id' => 1,
            'plan_id' => $planId,
            'period' => $planPeriod,
            'is_trial' => $isTrial,
            'is_grace' => $isGrace,
            'is_expired' => $isExpired,
            'ends_at' => '1 янв. 2030 г.',
            'ends_at_raw' => $endsAtRaw,
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
                    'third' => [
                        'id' => 1,
                        'name' => 'third',
                        'limit' => 5,
                        'unlimited' => 0,
                        'excess_price' => [
                            'raw' => 1200,
                            'formatted' => '12 руб.',
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
                'third' => [
                    'id' => 1,
                    'name' => 'third',
                    'limit' => 5,
                    'unlimited' => 0,
                    'left' => 5,
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
                    'charge_trials' => 1,
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
                    'charge_trials' => 1,
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
                    'charge_trials' => 1,
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
                        'created_at' => '2030-01-01 00:01:00',
                        'updated_at' => '2030-01-01 00:01:00',
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
                        'created_at' => '2030-01-01 00:00:00',
                        'updated_at' => '2030-01-01 00:00:00',
                    ],
                ],
            ]),
        ];
    }
}
