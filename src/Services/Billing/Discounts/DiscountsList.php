<?php

namespace Celestial\Services\Billing\Discounts;

use Celestial\Contracts\Api\ApiProviderContract;

trait DiscountsList
{
    /**
     * Загружает список скидок из указанного URL.
     *
     * @param \Celestial\Contracts\Api\ApiProviderContract $api
     * @param string                                       $url
     *
     * @return array
     */
    protected function loadDiscountsFrom(ApiProviderContract $api, string $url)
    {
        $response = $api->request('GET', $url);

        if ($response->failed()) {
            return [];
        }

        $discounts = [];

        foreach ($response->data() as $discount) {
            $discounts[] = new Discount($this->api, $discount);
        }

        return $discounts;
    }
}
