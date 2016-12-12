<?php

namespace Celestial\Contracts\Services\Billing;

use Celestial\Contracts\Services\Payments\PaymentsServiceContract;

interface BillingProfileContract
{
    /**
     * Возвращает данные профиля.
     *
     * @return array
     */
    public function getProfileData(): array;

    /**
     * Задает новые данные профиля.
     *
     * @param array $data
     *
     * @return \Celestial\Contracts\Services\Billing\BillingProfileContract
     */
    public function setProfileData(array $data);

    /**
     * Обновляет данные подписки профиля.
     *
     * @param array $data
     *
     * @return \Celestial\Contracts\Services\Billing\BillingProfileContract
     */
    public function setSubscriptionData(array $data);

    /**
     * Возвращает объект подписки профиля.
     *
     * @return \Celestial\Contracts\Services\Billing\SubscriptionContract
     */
    public function getSubscription();

    /**
     * Возвращает ID профиля.
     *
     * @return int
     */
    public function profileId(): int;

    /**
     * Возвращает ID пользователя профиля.
     *
     * @return int
     */
    public function userId(): int;

    /**
     * Возвращает платежный регион профиля.
     *
     * @return string | null
     */
    public function profileRegion();

    /**
     * Возвращает используемую валюту профиля.
     *
     * @return string | null
     */
    public function currency();

    /**
     * Возвращает название тарифного плана профиля.
     *
     * @return string | null
     */
    public function billingPlan();

    /**
     * Возвращает ID тарифного плана профиля.
     *
     * @return int
     */
    public function billingPlanId(): int;

    /**
     * Возвращает период подписки на тарифный план.
     *
     * @return string | null
     */
    public function billingPeriod();

    /**
     * Возвращает текущий баланс профиля в копейках.
     *
     * @return int
     */
    public function rawBalance();

    /**
     * Задает новый баланс профиля. Не вызывает изменений в удаленном сервисе.
     *
     * @param int $balance
     *
     * @return \Celestial\Contracts\Services\Billing\BillingProfileContract
     */
    public function setRawBalance(int $balance);

    /**
     * Возвращает текущий баланс профиля.
     *
     * @return string | null
     */
    public function balance();

    /**
     * Проверяет, подписан ли профиль на какой-либо тарифный план.
     *
     * @return bool
     */
    public function hasSubscription(): bool;

    /**
     * Проверяет, подписан ли профиль на пробную версию тарифа.
     *
     * @return bool
     */
    public function isOnTrial(): bool;

    /**
     * Проверяет, находится ли профиль на льготном периоде.
     *
     * @return bool
     */
    public function isOnGrace(): bool;

    /**
     * Выполняет переход профиля на другой тарифный план.
     *
     * @param \Celestial\Contracts\Services\Payments\PaymentsServiceContract $payments
     * @param string                                                         $email
     * @param string                                                         $plan
     * @param string                                                         $period
     * @param bool                                                           $isTrial  = false
     *
     * @throws \Celestial\Exceptions\Services\Billing\SubscriptionRequestFailedException
     *
     * @return \Celestial\Contracts\Services\Billing\SubscriptionResultContract
     */
    public function subscribe(PaymentsServiceContract $payments, string $email, string $plan, string $period, bool $isTrial = false);
}