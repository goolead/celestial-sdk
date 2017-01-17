<?php

use Celestial\Services\Billing\BillingService;

class DiscountsTest extends Requests
{
    protected function tearDown()
    {
        Mockery::close();
    }

    /** @test */
    function discounts_can_be_created()
    {
        $discountData = [
            'type' => 'fixed',
            'discount_type' => 'percentage',
            'entity_type' => 'feature',
            'entity_id' => 1,
            'value' => 5,
        ];

        $api = ServicesTestsHelper::mockApi(function ($api) use ($discountData) {
            $request = $this->getCreateDiscountRequest($discountData);

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'], $request['params'])
                ->andReturn($request['response']);
        });

        $service = new BillingService($api);
        $discount = $service->discounts()->create($discountData);

        $this->assertEquals('fixed', $discount->type());
        $this->assertEquals('percentage', $discount->discountType());
        $this->assertEquals(5, $discount->value());
    }

    /** @test */
    function discounts_can_be_listed()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $request = $this->getListDiscountsRequest();

            $api->shouldReceive('request')
                ->with($request['method'], $request['url'])
                ->andReturn($request['response']);
        });

        $service = new BillingService($api);
        $discounts = $service->discounts()->get();

        foreach ($discounts as $discount) {
            $this->assertInternalType('string', $discount->type());
            $this->assertInternalType('integer', $discount->value());
        }
    }

    /** @test */
    function discounts_can_be_applied_to_billing_profile()
    {
        $discountData = [
            'type' => 'fixed',
            'discount_type' => 'percentage',
            'entity_type' => 'feature',
            'entity_id' => 1,
            'value' => 5,
        ];

        $api = ServicesTestsHelper::mockApi(function ($api) use ($discountData) {
            $profileRequest = $this->getProfileByIdRequest();
            $requests = [
                $this->getCreateDiscountRequest($discountData),
                $this->getAttachDiscountRequest(1),
            ];

            $api->shouldReceive('request')
                ->with($profileRequest['method'], $profileRequest['url'])
                ->andReturn($profileRequest['response']);

            foreach ($requests as $request) {
                $api->shouldReceive('request')
                    ->with($request['method'], $request['url'], $request['params'])
                    ->andReturn($request['response']);
            }
        });

        $service = new BillingService($api);
        $discount = $service->discounts()->create($discountData);
        $profile = $service->getProfileById(1);

        $this->assertTrue($discount->applyTo($profile));
    }

    /** @test */
    function profile_discounts_can_be_listed()
    {
        $api = ServicesTestsHelper::mockApi(function ($api) {
            $requests = [
                $this->getProfileByIdRequest(),
                $this->getProfileDiscountsRequest(1),
            ];

            foreach ($requests as $request) {
                $api->shouldReceive('request')
                    ->with($request['method'], $request['url'])
                    ->andReturn($request['response']);
            }
        });

        $service = new BillingService($api);
        $profile = $service->getProfileById(1);
        $discounts = $profile->discounts();

        foreach ($discounts as $discount) {
            $this->assertInternalType('string', $discount->type());
            $this->assertInternalType('integer', $discount->value());
        }
    }

    /** @test */
    function discount_can_be_detached_from_billing_profile()
    {
        $discountData = [
            'type' => 'fixed',
            'discount_type' => 'percentage',
            'entity_type' => 'feature',
            'entity_id' => 1,
            'value' => 5,
        ];

        $api = ServicesTestsHelper::mockApi(function ($api) use ($discountData) {
            $getRequests = [
                $this->getProfileByIdRequest(),
                $this->getDetachDiscountRequest(1, 1),
            ];

            $requests = [
                $this->getCreateDiscountRequest($discountData),
                $this->getAttachDiscountRequest(1),
            ];

            foreach ($getRequests as $request) {
                $api->shouldReceive('request')
                    ->with($request['method'], $request['url'])
                    ->andReturn($request['response']);
            }

            foreach ($requests as $request) {
                $api->shouldReceive('request')
                    ->with($request['method'], $request['url'], $request['params'])
                    ->andReturn($request['response']);
            }
        });

        $service = new BillingService($api);
        $discount = $service->discounts()->create($discountData);
        $profile = $service->getProfileById(1);

        $this->assertTrue($discount->applyTo($profile));
        $this->assertTrue($discount->detachFrom($profile));
    }

    public function getAttachDiscountRequest($profileId)
    {
        return [
            'method' => 'POST',
            'url' => '/profiles/'.$profileId.'/discounts',
            'params' => [
                'form_params' => [
                    'discount_id' => 1,
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
            ]),
        ];
    }

    public function getDetachDiscountRequest($profileId, $discountId)
    {
        return [
            'method' => 'DELETE',
            'url' => '/profiles/'.$profileId.'/discounts/'.$discountId,
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
            ]),
        ];
    }

    public function getCreateDiscountRequest(array $data = [])
    {
        $type = $data['type'] ?? 'fixed';
        $discountType = $data['discount_type'] ?? 'percentage';
        $entityType = $data['entity_type'] ?? 'feature';
        $entityId = $data['entity_id'] ?? 1;
        $value = $data['value'] ?? 5;
        $maxValue = $data['max_value'] ?? $value;

        return [
            'method' => 'POST',
            'url' => '/discounts',
            'params' => [
                'form_params' => [
                    'type' => $type,
                    'discount_type' => $discountType,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'value' => $value,
                    'max_value' => $maxValue,
                ],
            ],
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => [
                    'id' => 1,
                    'type' => $type,
                    'discount_type' => $discountType,
                    'entity' => [
                        'id' => $entityId,
                        'type' => $entityType,
                    ],
                    'value' => $value,
                    'max_value' => $maxValue,
                    'applied_to' => [],
                    'created_at' => '2017-01-17 20:40:00',
                    'updated_at' => '2017-01-17 20:40:00',
                ],
            ]),
        ];
    }

    public function getListDiscountsRequest()
    {
        return [
            'method' => 'GET',
            'url' => '/discounts',
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => [
                    [
                        'id' => 1,
                        'type' => 'incremental',
                        'discount_type' => 'percentage',
                        'entity' => [
                            'id' => 1,
                            'type' => 'feature',
                        ],
                        'value' => 5,
                        'max_value' => 15,
                        'applied_to' => [],
                        'created_at' => '2017-01-17 20:40:00',
                        'updated_at' => '2017-01-17 20:40:00',
                    ],
                    [
                        'id' => 2,
                        'type' => 'fixed',
                        'discount_type' => 'percentage',
                        'entity' => [
                            'id' => 2,
                            'type' => 'feature',
                        ],
                        'value' => 5,
                        'max_value' => 5,
                        'applied_to' => [],
                        'created_at' => '2017-01-17 20:40:00',
                        'updated_at' => '2017-01-17 20:40:00',
                    ],
                    [
                        'id' => 3,
                        'type' => 'fixed',
                        'discount_type' => 'amount',
                        'entity' => [
                            'id' => 3,
                            'type' => 'feature',
                        ],
                        'value' => 1500,
                        'max_value' => 1500,
                        'applied_to' => [],
                        'created_at' => '2017-01-17 20:40:00',
                        'updated_at' => '2017-01-17 20:40:00',
                    ],
                ],
            ]),
        ];
    }

    public function getProfileDiscountsRequest($profileId)
    {
        return [
            'method' => 'GET',
            'url' => '/profiles/'.$profileId.'/discounts',
            'response' => ServicesTestsHelper::toApiResponse([
                'success' => 1,
                'data' => [
                    [
                        'id' => 1,
                        'type' => 'incremental',
                        'discount_type' => 'percentage',
                        'entity' => [
                            'id' => 1,
                            'type' => 'feature',
                        ],
                        'value' => 5,
                        'max_value' => 15,
                        'applied_to' => [],
                        'created_at' => '2017-01-17 20:40:00',
                        'updated_at' => '2017-01-17 20:40:00',
                    ],
                    [
                        'id' => 2,
                        'type' => 'fixed',
                        'discount_type' => 'percentage',
                        'entity' => [
                            'id' => 2,
                            'type' => 'feature',
                        ],
                        'value' => 5,
                        'max_value' => 5,
                        'applied_to' => [],
                        'created_at' => '2017-01-17 20:40:00',
                        'updated_at' => '2017-01-17 20:40:00',
                    ],
                    [
                        'id' => 3,
                        'type' => 'fixed',
                        'discount_type' => 'amount',
                        'entity' => [
                            'id' => 3,
                            'type' => 'feature',
                        ],
                        'value' => 1500,
                        'max_value' => 1500,
                        'applied_to' => [],
                        'created_at' => '2017-01-17 20:40:00',
                        'updated_at' => '2017-01-17 20:40:00',
                    ],
                ],
            ]),
        ];
    }
}
