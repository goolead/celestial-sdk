<?php

namespace Celestial\Contracts\Services\Billing;

interface SubscriptionContract
{
    /**
     * Задает новые данные подписки профиля.
     *
     * @param array $data
     *
     * @return \Celestial\Contracts\Services\Billing\SubscriptionContract
     */
    public function setData(array $data);

    /**
     * Задает новый баланс профиля. Не вызывает изменений в удаленном сервисе.
     *
     * @param int $balance
     *
     * @return \Celestial\Contracts\Services\Billing\SubscriptionContract
     */
    public function setBalance(int $balance);

    /**
     * Проверяет, имеется ли у профиля доступ к определенной возможности.
     *
     * @param string $feature
     *
     * @return bool
     */
    public function hasFeature(string $feature): bool;

    /**
     * Проверяет, является ли возможность безлимитной.
     *
     * @param string $feature
     *
     * @return bool
     */
    public function isUnlimitedFeature(string $feature): bool;

    /**
     * Проверяет, может ли профиль воспользоваться определенной возможностью тарифного плана.
     * Если $withBalance == true, будет проверена возможность использовать баланс профиля
     * для оплаты использования возможности.
     *
     * @param string $feature
     * @param bool   $withBalance = false
     * @param int    $value       = 1
     *
     * @return bool
     */
    public function canUseFeature(string $feature, bool $withBalance = false, int $value = 1);

    /**
     * Возвращает дату окончания подписки в человекочитаемом формате.
     *
     * @return string | null
     */
    public function endsAt();

    /**
     * Возвращает дату окончания подписки в формате "YYYY-MM-DD HH:MM:SS".
     *
     * @return string | null
     */
    public function endsAtRaw();
}
