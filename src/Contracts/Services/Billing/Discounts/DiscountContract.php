<?php

namespace Celestial\Contracts\Services\Billing\Discounts;

use Celestial\Contracts\Services\Billing\BillingProfileContract;

interface DiscountContract
{
    /**
     * ID скидки.
     *
     * @return int
     */
    public function id(): int;

    /**
     * Тип скидки (фиксированный, инкрементный и т.д).
     *
     * @return string
     */
    public function type(): string;

    /**
     * Тип скидки (проценты или целое значение).
     *
     * @return string
     */
    public function discountType(): string;

    /**
     * Значение скидки.
     *
     * @return int
     */
    public function value(): int;

    /**
     * Максимальное значение скидки.
     *
     * @return int
     */
    public function maxValue(): int;

    /**
     * Применяет скидку к переданному платежному профилю.
     *
     * @param \Celestial\Contracts\Services\Billing\BillingProfileContract $profile
     * @param string                                                       $applyTill = null
     *
     * @return bool
     */
    public function applyTo(BillingProfileContract $profile, string $applyTill = null): bool;
}
