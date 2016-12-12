<?php

namespace Celestial\Contracts\Services\Billing;

interface PlanContract
{
    /**
     * Задает новые данные тарифного плана.
     *
     * @param array $data
     *
     * @return \Celestial\Contracts\Services\Billing\PlanContract
     */
    public function setData(array $data);

    /**
     * Возвращает стоимость возможности при превышении лимитов.
     *
     * @param string $feature
     *
     * @return int
     */
    public function getFeatureExcessPrice(string $feature): int;
}
