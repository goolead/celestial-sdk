<?php

namespace Celestial\Contracts\Services\Billing\Discounts;

interface DiscountsManagerContract
{
    /**
     * Создает новую скидку.
     *
     * @param array  $attributes
     * @param array  $applyTo    = []
     * @param string $activeTill = null
     *
     * @return \Celestial\Contracts\Services\Billing\Discounts\DiscountContract
     */
    public function create(array $attributes, array $applyTo = [], string $activeTill = null);

    /**
     * Возвращает список всех скидок.
     *
     * @return array
     */
    public function get();

    /**
     * Выполняет поиск скидки по ID.
     *
     * @param int $id
     *
     * @return \Celestial\Contracts\Services\Billing\Discounts\DiscountContract|null
     */
    public function find(int $id);
}
