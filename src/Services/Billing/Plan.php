<?php

namespace Celestial\Services\Billing;

use Celestial\Contracts\Services\Billing\PlanContract;

class Plan implements PlanContract
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Задает новые данные тарифного плана.
     *
     * @param array $data
     *
     * @return \Celestial\Contracts\Services\Billing\PlanContract
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Возвращает стоимость возможности при превышении лимитов.
     *
     * @param string $feature
     *
     * @return int
     */
    public function getFeatureExcessPrice(string $feature): int
    {
        return intval($this->data['features'][$feature]['excess_price']['raw'] ?? 0);
    }
}
