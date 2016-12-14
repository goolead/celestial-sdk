<?php

namespace Celestial\Services\Billing;

use Celestial\Contracts\Services\Billing\BillingServiceContract;
use Celestial\Contracts\Services\Webhooks\CreatesWebhooks;
use Celestial\Exceptions\Services\Billing\ProfileWasNotCreatedException;
use Celestial\Exceptions\Services\Billing\ProfileWasNotFoundException;
use Celestial\Services\AbstractService;
use Celestial\Services\Webhooks\Traits\Webhooks;
use Illuminate\Support\Collection;

class BillingService extends AbstractService implements BillingServiceContract, CreatesWebhooks
{
    use Webhooks;

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
}
