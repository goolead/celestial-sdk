<?php

namespace Celestial\Contracts\Services\Billing;

interface BillingServiceContract
{
    /**
     * Создает новый платежный профиль.
     *
     * @param array $data
     *
     * @throws \InvalidArgumentException
     * @throws \Celestial\Exceptions\Services\Billing\ProfileWasNotCreatedException
     *
     * @return \Celestial\Contracts\Services\Billing\BillingProfileContract
     */
    public function createProfile(array $data);

    /**
     * Загружает профиль по ID.
     *
     * @param int $id
     *
     * @throws \Celestial\Exceptions\Services\Billing\ProfileWasNotFoundException
     *
     * @return \Celestial\Services\Billing\BillingProfile
     */
    public function getProfileById(int $id);

    /**
     * Загружает профиль по ID пользователя.
     *
     * @param int $userId
     *
     * @throws \Celestial\Exceptions\Services\Billing\ProfileWasNotFoundException
     *
     * @return \Celestial\Services\Billing\BillingProfile
     */
    public function getProfileByUserId(int $userId);
}
