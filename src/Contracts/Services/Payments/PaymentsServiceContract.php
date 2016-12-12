<?php

namespace Celestial\Contracts\Services\Payments;

interface PaymentsServiceContract
{
    /**
     * Задает платежную систему, используемую по умолчанию.
     *
     * @param string $provider
     *
     * @return \Celstial\Contracts\Services\Payments\PaymentsServiceContract
     */
    public function setDefaultProvider(string $provider);

    /**
     * Инициализирует платежную сессию.
     *
     * @param array  $user
     * @param int    $amount
     * @param string $provider      = null
     * @param bool   $isRecurrent   = false
     * @param array  $notifications = []
     *
     * @throws \Celestial\Exceptions\Services\Payments\DefaultPaymentsServiceProviderException
     * @throws \Celestial\Exceptions\Services\Payments\UnableToInitializePaymentSessionException
     *
     * @return \Celestial\Contracts\Services\Payments\PaymentSessionContract
     */
    public function initSession(array $user, int $amount, string $provider = null, bool $isRecurrent = false, array $notifications = []);
}
