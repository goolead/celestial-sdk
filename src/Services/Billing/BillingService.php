<?php

namespace Celestial\Services\Billing;

use Celestial\Contracts\Services\Billing\BillingServiceContract;
use Celestial\Contracts\Services\Webhooks\CreatesWebhooks;
use Celestial\Exceptions\Services\Billing\ProfileWasNotCreatedException;
use Celestial\Exceptions\Services\Billing\ProfileWasNotFoundException;
use Celestial\Services\AbstractService;
use Celestial\Services\Billing\Discounts\DiscountsManager;
use Celestial\Services\Webhooks\Traits\Webhooks;
use Illuminate\Support\Collection;

class BillingService extends AbstractService implements BillingServiceContract, CreatesWebhooks
{
    use Webhooks;

    /**
     * @var \Celestial\Contracts\Services\Billing\Discounts\DiscountsManagerContract
     */
    protected $discounts;

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
    public function createProfile(array $data)
    {
        $this->checkForRequiredFields($data, [
            'user_id', 'region',
        ]);

        $response = $this->api->request('POST', '/profiles', [
            'form_params' => [
                'user_id' => $data['user_id'],
                'region' => $data['region'],
                'balance' => intval($data['balance'] ?? 0),
                'plan' => $data['plan'] ?? '',
                'period' => $data['period'] ?? '',
                'trial' => intval($data['trial'] ?? 0),
                'ends_at' => $data['ends_at'] ?? null,
                'discount' => $data['discount'] ?? null,
            ],
        ]);

        if ($response->failed()) {
            throw new ProfileWasNotCreatedException('Unable to create profile. Request status: '.$response->statusCode());
        }

        return new BillingProfile($this->api, $response->data());
    }

    /**
     * Загружает профиль по ID.
     *
     * @param int $id
     *
     * @throws \Celestial\Exceptions\Services\Billing\ProfileWasNotFoundException
     *
     * @return \Celestial\Services\Billing\BillingProfile
     */
    public function getProfileById(int $id)
    {
        $response = $this->api->request('GET', '/profiles/'.$id);

        if ($response->failed()) {
            throw new ProfileWasNotFoundException('Unable to find profile by id "'.$id.'"');
        }

        return new BillingProfile($this->api, $response->data());
    }

    /**
     * Загружает несколько профилей по их ID.
     *
     * @param array $ids
     *
     * @throws \Celestial\Exceptions\Services\Billing\ProfileWasNotFoundException
     *
     * @return array
     */
    public function getProfilesByIds(array $ids)
    {
        $response = $this->api->request('GET', '/profiles', [
            'query' => [
                'ids' => $this->transformArrayToIdsList($ids),
            ],
        ]);

        if ($response->failed()) {
            throw new ProfileWasNotFoundException('Unable load profiles by IDs.');
        }

        return $this->transformArrayToProfiles($response->data());
    }

    /**
     * Загружает профиль по ID пользователя.
     *
     * @param int $userId
     *
     * @throws \Celestial\Exceptions\Services\Billing\ProfileWasNotFoundException
     *
     * @return \Celestial\Services\Billing\BillingProfile
     */
    public function getProfileByUserId(int $userId)
    {
        $response = $this->api->request('GET', '/users/'.$userId);

        if ($response->failed()) {
            throw new ProfileWasNotFoundException('Unable to find profile by user id "'.$userId.'"');
        }

        return new BillingProfile($this->api, $response->data());
    }

    /**
     * Загружает несколько профилей по ID пользователей.
     *
     * @param array $userIds
     *
     * @throws \Celestial\Exceptions\Services\Billing\ProfileWasNotFoundException
     *
     * @return \Celestial\Services\Billing\BillingProfile
     */
    public function getProfilesByUserIds(array $userIds)
    {
        $response = $this->api->request('GET', '/users', [
            'query' => [
                'user_ids' => $this->transformArrayToIdsList($userIds),
            ],
        ]);

        if ($response->failed()) {
            throw new ProfileWasNotFoundException('Unable load profiles by user IDs.');
        }

        return $this->transformArrayToProfiles($response->data());
    }

    /**
     * Преобразует список профилей в объекты.
     *
     * @param array $data
     *
     * @return array
     */
    protected function transformArrayToProfiles(array $data)
    {
        $profiles = [];

        foreach ($data as $item) {
            $profiles[] = new BillingProfile($this->api, $item);
        }

        return $profiles;
    }

    /**
     * Приводит массив ID к integer, отсекает нули и склеивает оставшиеся значения запятыми.
     *
     * @param array $ids
     *
     * @return string
     */
    protected function transformArrayToIdsList(array $ids)
    {
        $ids = array_map(function ($id) {
            return intval($id);
        }, $ids);

        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return implode(',', $ids);
    }

    /**
     * Загружает тарифные планы для выбранного региона.
     *
     * @param string $region
     *
     * @return array
     */
    public function plans(string $region)
    {
        $response = $this->api->request('GET', '/plans');

        if ($response->failed()) {
            throw new PlansRequestFailedException('Remote service answered with status '.$response->statusCode().'.');
        }

        return (new Collection($response->data()))
            ->map(function ($plan) use ($region) {
                $prices = (new Collection($plan['periods']))
                    ->map(function ($periodPrices, $period) use ($region) {
                        return [
                            'period' => $period,
                            'price' => (new Collection($periodPrices))
                                ->reject(function ($price) use ($region) {
                                    return $price['region'] !== $region;
                                })
                                ->first()['price'],
                        ];
                    });

                unset($plan['periods']);
                $plan['prices'] = $prices->toArray();

                return $plan;
            })
            ->toArray();
    }

    /**
     * Возвращает менеджер скидок.
     *
     * @return \Celestial\Contracts\Services\Billing\Discounts\DiscountsManagerContract
     */
    public function discounts()
    {
        if (!is_null($this->discounts)) {
            return $this->discounts;
        }

        return $this->discounts = new DiscountsManager($this->api);
    }
}
