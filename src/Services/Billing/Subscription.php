<?php

namespace Celestial\Services\Billing;

use Celestial\Contracts\Services\Billing\SubscriptionContract;

class Subscription implements SubscriptionContract
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var \Celestial\Contracts\Services\Billing\PlanContract
     */
    protected $plan;

    /**
     * @var int
     */
    protected $balance = 0;

    /**
     * @param array $data
     * @param int   $balance
     */
    public function __construct(array $data, int $balance = 0)
    {
        $this->data = $data;
        $this->plan = new Plan($data['plan']);
        $this->balance = $balance;
    }

    /**
     * Задает новые данные подписки профиля.
     *
     * @param array $data
     *
     * @return \Celestial\Contracts\Services\Billing\SubscriptionContract
     */
    public function setData(array $data)
    {
        $this->data = $data;
        $this->plan->setData($this->data['plan'] ?? []);

        return $this;
    }

    /**
     * Задает новый баланс профиля. Не вызывает изменений в удаленном сервисе.
     *
     * @param int $balance
     *
     * @return \Celestial\Contracts\Services\Billing\SubscriptionContract
     */
    public function setBalance(int $balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Проверяет, имеется ли у профиля доступ к определенной возможности.
     *
     * @param string $feature
     *
     * @return bool
     */
    public function hasFeature(string $feature): bool
    {
        return isset($this->data['features'][$feature]);
    }

    /**
     * Проверяет, является ли возможность безлимитной.
     *
     * @param string $feature
     *
     * @return bool
     */
    public function isUnlimitedFeature(string $feature): bool
    {
        return intval($this->data['features'][$feature]['unlimited'] ?? 0) === 1;
    }

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
    public function canUseFeature(string $feature, bool $withBalance = false, int $value = 1)
    {
        if ($this->isUnlimitedFeature($feature)) {
            return true;
        }

        $amountLeft = intval($this->data['features'][$feature]['left'] ?? 0);
        $canUseWithinLimits = ($amountLeft - $value) >= 0;

        if ($canUseWithinLimits === true) {
            return true;
        }

        if ($withBalance === false) {
            return false;
        }

        $price = $this->plan->getFeatureExcessPrice($feature);

        if ($price === 0) {
            return true;
        }

        return $this->balance >= ($price * $value);
    }

    /**
     * Возвращает дату окончания подписки в человекочитаемом формате.
     *
     * @return string | null
     */
    public function endsAt()
    {
        return $this->data['end_at'] ?? null;
    }

    /**
     * Возвращает дату окончания подписки в формате "YYYY-MM-DD HH:MM:SS".
     *
     * @return string | null
     */
    public function endsAtRaw()
    {
        return $this->data['ends_at_raw'] ?? null;
    }
}
