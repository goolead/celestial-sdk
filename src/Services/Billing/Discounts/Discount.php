<?php

namespace Celestial\Services\Billing\Discounts;

use Celestial\Contracts\Api\ApiProviderContract;
use Celestial\Contracts\Services\Billing\BillingProfileContract;
use Celestial\Contracts\Services\Billing\Discounts\DiscountContract;
use Celestial\Exceptions\Services\Billing\DiscountWasNotAppliedException;

class Discount implements DiscountContract
{
    /**
     * @var \Celestial\Contracts\Api\ApiProviderContract
     */
    protected $api;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param \Celestial\Contracts\Api\ApiProviderContract $api
     * @param array                                        $data = []
     */
    public function __construct(ApiProviderContract $api, array $data = [])
    {
        $this->api = $api;
        $this->data = $data;
    }

    /**
     * ID скидки.
     *
     * @return int
     */
    public function id(): int
    {
        return intval($this->data['id'] ?? 0);
    }

    /**
     * Тип скидки (фиксированный, инкрементный и т.д).
     *
     * @return string
     */
    public function type(): string
    {
        return $this->data['type'] ?? '0';
    }

    /**
     * Тип скидки (проценты или целое значение).
     *
     * @return string
     */
    public function discountType(): string
    {
        return $this->data['discount_type'] ?? '0';
    }

    /**
     * Значение скидки.
     *
     * @return int
     */
    public function value(): int
    {
        return intval($this->data['value'] ?? 0);
    }

    /**
     * Максимальное значение скидки.
     *
     * @return int
     */
    public function maxValue(): int
    {
        return intval($this->data['max_value'] ?? 0);
    }

    /**
     * Применяет скидку к переданному платежному профилю.
     *
     * @param \Celestial\Contracts\Services\Billing\BillingProfileContract $profile
     * @param string                                                       $applyTill = null
     *
     * @throws \Celestial\Exceptions\Services\Billing\DiscountWasNotAppliedException
     *
     * @return bool
     */
    public function applyTo(BillingProfileContract $profile, string $applyTill = null): bool
    {
        $form = [
            'discount_id' => $this->id(),
        ];

        if (!is_null($applyTill)) {
            $form['apply_till'] = $applyTill;
        }

        $response = $this->api->request('POST', '/profiles/'.$profile->profileId().'/discounts', [
            'form_params' => $form,
        ]);

        if ($response->failed()) {
            throw new DiscountWasNotAppliedException('Unable to apply discount. Response status: '.$response->statusCode());
        }

        return true;
    }
}
