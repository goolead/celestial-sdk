<?php

namespace Celestial\Services\Billing;

use Celestial\Contracts\Api\ApiProviderContract;
use Celestial\Contracts\Services\Billing\BillingProfileContract;
use Celestial\Contracts\Services\Payments\PaymentSessionContract;
use Celestial\Contracts\Services\Payments\PaymentsServiceContract;

class BillingProfile implements BillingProfileContract
{
    const HTTP_UNPROCESSABLE_ENTITY = 422;

    /**
     * @var \Celestial\Contracts\Api\ApiProviderContract
     */
    protected $api;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var \Celestial\Contracts\Services\Billing\SubscriptionContract
     */
    protected $subscription;

    /**
     * @param array $data
     */
    public function __construct(ApiProviderContract $api, array $data)
    {
        $this->api = $api;
        $this->data = $data;
    }

    /**
     * Возвращает данные профиля.
     *
     * @return array
     */
    public function getProfileData(): array
    {
        return $this->data;
    }

    /**
     * Задает новые данные профиля.
     *
     * @param array $data
     *
     * @return \Celestial\Contracts\Services\Billing\BillingProfileContract
     */
    public function setProfileData(array $data)
    {
        $this->data = $data;

        if (!is_null($this->subscription)) {
            $this->subscription
                ->setData($this->data['subscription'] ?? [])
                ->setBalance($this->rawBalance());
        }

        return $this;
    }

    /**
     * Обновляет данные подписки профиля.
     *
     * @param array $data
     *
     * @return \Celestial\Contracts\Services\Billing\BillingProfileContract
     */
    public function setSubscriptionData(array $data)
    {
        $this->data['subscription'] = $data;

        if (!is_null($this->subscription)) {
            $this->subscription->setData($data);
        }

        return $this;
    }

    /**
     * Возвращает объект подписки профиля.
     *
     * @return \Celestial\Contracts\Services\Billing\SubscriptionContract
     */
    public function getSubscription()
    {
        if (!is_null($this->subscription)) {
            return $this->subscription;
        }

        return $this->subscription = new Subscription($this->data['subscription'] ?? [], $this->rawBalance());
    }

    /**
     * Возвращает ID профиля.
     *
     * @return int
     */
    public function profileId(): int
    {
        return intval($this->data['id'] ?? 0);
    }

    /**
     * Возвращает ID пользователя профиля.
     *
     * @return int
     */
    public function userId(): int
    {
        return intval($this->data['user_id'] ?? 0);
    }

    /**
     * Возвращает платежный регион профиля.
     *
     * @return string | null
     */
    public function profileRegion()
    {
        return $this->data['region'] ?? null;
    }

    /**
     * Возвращает используемую валюту профиля.
     *
     * @return string | null
     */
    public function currency()
    {
        return $this->data['currency'] ?? null;
    }

    /**
     * Возвращает название тарифного плана профиля.
     *
     * @return string | null
     */
    public function billingPlan()
    {
        return $this->data['subscription']['plan']['name'] ?? null;
    }

    /**
     * Возвращает ID тарифного плана профиля.
     *
     * @return int
     */
    public function billingPlanId(): int
    {
        return intval($this->data['subscription']['plan_id'] ?? 0);
    }

    /**
     * Возвращает период подписки на тарифный план.
     *
     * @return string | null
     */
    public function billingPeriod()
    {
        return $this->data['subscription']['period'] ?? null;
    }

    /**
     * Возвращает текущий баланс профиля в копейках.
     *
     * @return int
     */
    public function rawBalance()
    {
        return intval($this->data['balance']['raw'] ?? 0);
    }

    /**
     * Задает новый баланс профиля. Не вызывает изменений в удаленном сервисе.
     *
     * @param int $balance
     *
     * @return \Celestial\Contracts\Services\Billing\BillingProfileContract
     */
    public function setRawBalance(int $balance)
    {
        if (!isset($this->data['balance'])) {
            $this->data['balance'] = [];
        }

        $this->data['balance']['raw'] = $balance;

        if (!is_null($this->subscription)) {
            $this->subscription->setBalance($balance);
        }

        return $this;
    }

    /**
     * Возвращает текущий баланс профиля.
     *
     * @return string | null
     */
    public function balance()
    {
        return $this->data['balance']['formatted'] ?? null;
    }

    /**
     * Проверяет, подписан ли профиль на какой-либо тарифный план.
     *
     * @return bool
     */
    public function hasSubscription(): bool
    {
        return $this->billingPlanId() !== 0;
    }

    /**
     * Проверяет, подписан ли профиль на пробную версию тарифа.
     *
     * @return bool
     */
    public function isOnTrial(): bool
    {
        return intval($this->data['subscription']['is_trial'] ?? 0) === 1;
    }

    /**
     * Проверяет, находится ли профиль на льготном периоде.
     *
     * @return bool
     */
    public function isOnGrace(): bool
    {
        return intval($this->data['subscription']['is_grace'] ?? 0) === 1;
    }

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
    public function subscribe(PaymentsServiceContract $payments, string $email, string $plan, string $period, bool $isTrial = false)
    {
        $form = [
            'plan' => $plan,
            'period' => $period,
            'trial' => $isTrial ? 1 : 0,
        ];

        $response = $this->api->request('PUT', '/profiles/'.$this->profileId().'/subscription', [
            'form_params' => $form,
        ]);

        $profileHasSubscription = true;

        if ($response->statusCode() === static::HTTP_UNPROCESSABLE_ENTITY) {
            // Профиль не подписан.
            $profileHasSubscription = false;

            $response = $this->api->request('POST', '/profiles/'.$this->profileId().'/subscription', [
                'form_params' => $form,
            ]);
        }

        $subscriptionResult = new SubscriptionResult($response->response(), $response->statusCode());

        if ($subscriptionResult->subscriptionUpdated()) {
            $this->setSubscriptionData($response->data());
        } elseif ($subscriptionResult->paymentRequired()) {
            // Требуется пополнение баланса перед сменой тарифа.
            // Необходимо инициализировать платежную сессию с последующим
            // уведомлением биллинга о получении средств и автоперевода на выбранный тариф.

            $notification = [
                'type' => 'confirmed',
                'service' => 'billing',
                'service_token' => $this->api->token(),
                'url' => $this->api->resolveUrl('/profiles/'.$this->profileId().'/subscription'),
                'method' => ($profileHasSubscription ? 'PUT' : 'POST'),
                'form' => $form,
            ];

            $userData = [
                'id' => $this->userId(),
                'email' => $email,
                'currency' => $this->currency(),
            ];

            $subscriptionResult->initPayment($payments, $userData, [$notification]);
        }

        return $subscriptionResult;
    }

    /**
     * Запрашивает историю изменения баланса профиля.
     *
     * @param string $timezone = null
     *
     * @throws \BalanceHistoryRequestFailedException
     *
     * @return array
     */
    public function balanceHistory(string $timezone = null)
    {
        $params = [];

        if (!is_null($timezone)) {
            $params['query'] = $params['query'] ?? [];
            $params['query']['timezone'] = $timezone;
        }

        $response = $this->api->request('GET', '/profiles/'.$this->profileId().'/balance/history', $params);

        if ($response->failed()) {
            throw new BalanceHistoryRequestFailedException('Remote service answered with status '.$response->statusCode().'.');
        }

        return $response->data();
    }

    /**
     * Отправляет уведомление о принятом платеже в удаленный сервис.
     *
     * @param \Celestial\Contracts\Services\Payments\PaymentSessionContract $session
     */
    public function paymentAccepted(PaymentSessionContract $session)
    {
        $params = [
            'form_params' => [
                'amount' => $session->amount(),
                'transaction' => $session->uuid(),
                'system' => $session->provider(),
                'meta' => [
                    'recurrent' => $session->isRecurrent() ? 1 : 0,
                ],
            ],
        ];

        $this->api->request('POST', '/users/'.$this->userId().'/payments', $params);
    }

    /**
     * Отправляет уведомление об отмененном платеже в удаленный сервис.
     *
     * @param \Celestial\Contracts\Services\Payments\PaymentSessionContract $session
     */
    public function paymentCancelled(PaymentSessionContract $session)
    {
        $params = [
            'form_params' => [
                'amount' => $session->amount(),
                'type' => 'payments.cancel',
                'meta' => [
                    'transaction' => $session->uuid(),
                ],
            ],
        ];

        $this->api->request('DELETE', '/users/'.$this->userId().'/payments', $params);
    }

    /**
     * Отправляет уведомление о возвращенном платеже в удаленный сервис.
     *
     * @param \Celestial\Contracts\Services\Payments\PaymentSessionContract $session
     */
    public function paymentRefunded(PaymentSessionContract $session)
    {
        $params = [
            'form_params' => [
                'amount' => $session->amount(),
                'type' => 'payments.refund',
                'meta' => [
                    'transaction' => $session->uuid(),
                ],
            ],
        ];

        $this->api->request('DELETE', '/users/'.$this->userId().'/payments', $params);
    }
}
