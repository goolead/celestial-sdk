<?php

namespace Celestial\Services\Billing;

use Celestial\Contracts\Services\Billing\SubscriptionResultContract;
use Celestial\Contracts\Services\Payments\PaymentsServiceContract;
use JsonSerializable;

class SubscriptionResult implements SubscriptionResultContract, JsonSerializable
{
    const HTTP_PAYMENT_REQUIRED = 402;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $paymentUrl;

    /**
     * @param array $data
     */
    public function __construct(array $data, int $statusCode)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
    }

    /**
     * Возвращает данные для сериализации JSON.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'data' => $this->data,
            'status_code' => $this->statusCode,
            'payment_url' => $this->paymentUrl,
        ];
    }

    /**
     * Проверяет, завершилась ли смена тарифа успехом.
     *
     * @return bool
     */
    public function subscriptionUpdated(): bool
    {
        return intval($this->data['success'] ?? 0) === 1;
    }

    /**
     * Проверяет, требуется ли пополнение баланса для перехода на тариф.
     *
     * @return bool
     */
    public function paymentRequired(): bool
    {
        return $this->statusCode === static::HTTP_PAYMENT_REQUIRED;
    }

    /**
     * Возвращает сумму, на которую необходимо пополнить баланс для перехода на тариф.
     *
     * @return int
     */
    public function requiredAmount(): int
    {
        return intval($this->data['data']['required'] ?? 0);
    }

    /**
     * Возвращает форматированную сумму, на которую необходимо пополнить баланс для перехода на тариф.
     *
     * @return string | null
     */
    public function requiredAmountFormatted()
    {
        return $this->data['data']['required_formatted'] ?? null;
    }

    /**
     * Задает URL платежной формы.
     *
     * @param string $paymentUrl
     *
     * @return \Celestial\Contracts\Services\Billing\SubscriptionResultContract
     */
    public function setPaymentUrl(string $paymentUrl)
    {
        $this->paymentUrl = $paymentUrl;

        return $this;
    }

    /**
     * Возвращает URL платежной формы.
     *
     * @return string | null
     */
    public function paymentUrl()
    {
        return $this->paymentUrl;
    }

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
    public function initPayment(PaymentsServiceContract $payments, array $userData, array $notifications = [], string $provider = null)
    {
        $session = $payments->initSession($userData, $this->requiredAmount(), $provider, false, $notifications);

        $this->setPaymentUrl($session->paymentUrl());

        return $this;
    }
}
