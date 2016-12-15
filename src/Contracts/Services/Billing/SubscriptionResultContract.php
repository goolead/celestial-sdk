<?php

namespace Celestial\Contracts\Services\Billing;

use Celestial\Contracts\Services\Payments\PaymentsServiceContract;

interface SubscriptionResultContract
{
    /**
     * Проверяет, завершилась ли смена тарифа успехом.
     *
     * @return bool
     */
    public function subscriptionUpdated(): bool;

    /**
     * Проверяет, требуется ли пополнение баланса для перехода на тариф.
     *
     * @return bool
     */
    public function paymentRequired(): bool;

    /**
     * Возвращает сумму, на которую необходимо пополнить баланс для перехода на тариф.
     *
     * @return int
     */
    public function requiredAmount(): int;

    /**
     * Возвращает форматированную сумму, на которую необходимо пополнить баланс для перехода на тариф.
     *
     * @return string | null
     */
    public function requiredAmountFormatted();

    /**
     * Задает URL платежной формы.
     *
     * @param string $paymentUrl
     *
     * @return \Celestial\Contracts\Services\Billing\SubscriptionResultContract
     */
    public function setPaymentUrl(string $paymentUrl);

    /**
     * Возвращает URL платежной формы.
     *
     * @return string | null
     */
    public function paymentUrl();

    /**
     * Инициализирует платежную сессии для оплаты тарифного плана.
     *
     * @param \Celestial\Contracts\Services\Payments\PaymentsServiceContract $payments
     * @param array                                                          $userData
     * @param array                                                          $notifications = []
     * @param string                                                         $provider      = null
     *
     * @return \Celestial\Contracts\Services\Billing\SubscriptionResultContract
     */
    public function initPayment(PaymentsServiceContract $payments, array $userData, array $notifications = [], string $provider = null);
}
