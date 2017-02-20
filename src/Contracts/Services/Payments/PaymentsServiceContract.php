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

    /**
     * Выполняет попытку провести рекуррентный платеж для переданного пользователя.
     *
     * @param int $userId
     * @param int $amount = 0
     *
     * @return array
     */
    public function processRecurrentPayment(int $userId, int $amount = 0);

    /**
     * Проверяет, есть ли у пользователя активная рекуррентная сессия.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function hasRecurrentSession(int $userId): bool;

    /**
     * Возвращает активную рекуррентную сессию для выбранного пользователя.
     *
     * @param int $userId
     *
     * @return array|null
     */
    public function recurrentSessionFor(int $userId);

    /**
     * Удаляет сохраненную рекуррентную сессию пользователя.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function deleteRecurrentSession(int $userId): bool;
}
