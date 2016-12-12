<?php

namespace Celestial\Contracts\Api;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

interface ApiProviderContract
{
    /**
     * Переопределяет HTTP-клиента.
     *
     * @param \GuzzleHttp\ClientInterface $client
     *
     * @return \Celestial\Api\ApiProvider
     */
    public function setClient(ClientInterface $client);

    /**
     * Возвращает HTTP-клиент.
     *
     * @return \GuzzleHttp\ClientInterface
     */
    public function getClient();

    /**
     * Возвращает API-токен.
     *
     * @return string | null
     */
    public function token();

    /**
     * Возвращает полный URL по отношению к базовому.
     *
     * @param string $url
     *
     * @return string
     */
    public function resolveUrl(string $url);

    /**
     * Выполняет HTTP-запрос к удаленному сервису.
     *
     * @param string $method
     * @param string $url
     * @param array  $params = []
     *
     * @return \Celestial\Api\ApiResponse
     */
    public function request(string $method, string $url, array $params = []);

    /**
     * Трансформирует ответ от сервиса в общий интерфейс.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @throws \Celestial\Exceptions\Api\EmptyApiResponseException
     * @throws \Celestial\Exceptions\Api\InvalidJsonResponseException
     */
    public function transformResponse(ResponseInterface $response);
}
