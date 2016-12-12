<?php

namespace Celestial\Contracts\Services\Payments;

interface PaymentSessionContract
{
    /**
     * Возвращает ID пользователя.
     *
     * @return int
     */
    public function userId(): int;

    /**
     * Возвращает email пользователя.
     *
     * @return string | null
     */
    public function userEmail();

    /**
     * Возвращает платежную систему, использованную в запросе.
     *
     * @return string | null
     */
    public function provider();

    /**
     * Возвращает сумму платежа в копейках.
     *
     * @return int | null
     */
    public function amount();

    /**
     * Возвращает валюту платежа.
     *
     * @return string | null
     */
    public function currency();

    /**
     * Проверяет, является ли платеж рекуррентным.
     *
     * @return bool
     */
    public function isRecurrent(): bool;

    /**
     * Возвращает URL, на который необходимо перенаправить пользователя для совершения оплаты.
     *
     * @return string | null
     */
    public function paymentUrl();
}
