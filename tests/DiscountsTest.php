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
}
