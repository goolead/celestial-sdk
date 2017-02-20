<?php

namespace Celestial\Services\Payments;

use Celestial\Contracts\Services\Payments\PaymentsServiceContract;
use Celestial\Contracts\Services\Webhooks\CreatesWebhooks;
use Celestial\Exceptions\Services\Payments\DefaultPaymentsServiceProviderException;
use Celestial\Exceptions\Services\Payments\UnableToInitializePaymentSessionException;
use Celestial\Services\AbstractService;
use Celestial\Services\Webhooks\Traits\Webhooks;

class PaymentsService extends AbstractService implements PaymentsServiceContract, CreatesWebhooks
{
    use Webhooks;

    /**
     * @var string
     */
    protected $defaultProvider;

    /**
     * Задает платежную систему, используемую по умолчанию.
     *
     * @param string $provider
     *
     * @return \Celstial\Contracts\Services\Payments\PaymentsServiceContract
     */
    public function setDefaultProvider(string $provider)
    {
        $this->defaultProvider = $provider;

        return $this;
    }

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
    public function initSession(array $user, int $amount, string $provider = null, bool $isRecurrent = false, array $notifications = [])
    {
        if (is_null($provider)) {
            $provider = $this->defaultProvider;
        }

        if (is_null($provider)) {
            throw new DefaultPaymentsServiceProviderException('Default provider is not defined.');
        }

        $this->checkForRequiredFields($user, ['currency', 'id', 'email']);

        $response = $this->api->request('POST', '/payments/init', [
            'form_params' => [
                'provider' => $provider,
                'amount' => $amount,
                'currency' => $user['currency'],
                'user_id' => $user['id'],
                'email' => $user['email'],
                'recurrent' => $isRecurrent ? 1 : 0,
                'notifications' => $notifications,
            ],
        ]);

        if ($response->failed()) {
            throw new UnableToInitializePaymentSessionException('Remote service answered with status '.$response->statusCode().'.');
        }

        $paymentUrl = $response->data()['response']['payment_url'] ?? null;

        if (is_null($paymentUrl)) {
            throw new UnableToInitializePaymentSessionException('Remote service was unable to provide payment url.');
        }

        return new PaymentSession([
            'user' => $user,
            'amount' => $amount,
            'currency' => $user['currency'],
            'recurrent' => $isRecurrent,
            'notifications' => $notifications,
            'payment_url' => $paymentUrl,
        ]);
    }

    /**
     * Выполняет попытку провести рекуррентный платеж для переданного пользователя.
     *
     * @param int $userId
     * @param int $amount = 0
     *
     * @return array
     */
    public function processRecurrentPayment(int $userId, int $amount = 0)
    {
        $response = $this->api->request('POST', '/payments/rebill', [
            'form_params' => [
                'user_id' => $userId,
                'amount' => $amount,
            ],
        ]);

        return $response->data();
    }

    /**
     * Проверяет, есть ли у пользователя активная рекуррентная сессия.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function hasRecurrentSession(int $userId): bool
    {
        $recurrentSession = $this->recurrentSessionFor($userId);

        return !is_null($recurrentSession);
    }

    /**
     * Возвращает активную рекуррентную сессию для выбранного пользователя.
     *
     * @param int $userId
     *
     * @return array|null
     */
    public function recurrentSessionFor(int $userId)
    {
        $response = $this->api->request('GET', '/payments/recurrent', [
            'query' => [
                'user_id' => $userId,
            ],
        ]);

        if ($response->requestFailed()) {
            return;
        }

        return $response->data();
    }

    /**
     * Удаляет сохраненную рекуррентную сессию пользователя.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function deleteRecurrentSession(int $userId): bool
    {
        $response = $this->api->request('DELETE', '/payments/recurrent', [
            'query' => [
                'user_id' => $userId,
            ],
        ]);

        return !$response->requestFailed();
    }
}
