<?php

namespace Celestial\Services\Payments;

use Celestial\Contracts\Services\Payments\PaymentSessionContract;

class PaymentSession implements PaymentSessionContract
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Возвращает уникальный идентификатор платежной сессии.
     *
     * @return string | null
     */
    public function uuid()
    {
        return $this->data['uuid'] ?? null;
    }

    /**
     * Возвращает ID пользователя.
     *
     * @return int
     */
    public function userId(): int
    {
        return intval($this->data['user']['id'] ?? 0);
    }

    /**
     * Возвращает email пользователя.
     *
     * @return string | null
     */
    public function userEmail()
    {
        return $this->data['user']['email'] ?? null;
    }

    /**
     * Возвращает платежную систему, использованную в запросе.
     *
     * @return string | null
     */
    public function provider()
    {
        return $this->data['provider'] ?? null;
    }

    /**
     * Возвращает сумму платежа в копейках.
     *
     * @return int | null
     */
    public function amount()
    {
        return intval($this->data['amount'] ?? 0);
    }

    /**
     * Возвращает валюту платежа.
     *
     * @return string | null
     */
    public function currency()
    {
        return $this->data['currency'] ?? null;
    }

    /**
     * Проверяет, является ли платеж рекуррентным.
     *
     * @return bool
     */
    public function isRecurrent(): bool
    {
        return $this->data['recurrent'] ?? false;
    }

    /**
     * Возвращает URL, на который необходимо перенаправить пользователя для совершения оплаты.
     *
     * @return string | null
     */
    public function paymentUrl()
    {
        return $this->data['payment_url'] ?? null;
    }
}
