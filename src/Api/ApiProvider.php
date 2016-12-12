<?php

namespace Celestial\Api;

use Celestial\Contracts\Api\ApiProviderContract;
use Celestial\Exceptions\Api\EmptyApiResponseException;
use Celestial\Exceptions\Api\InvalidJsonResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class ApiProvider implements ApiProviderContract
{
    /**
     * @var string
     */
    protected $baseUri;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * @param string $baseUri = null
     * @param string $token   = null
     */
    public function __construct(string $baseUri = null, string $token = null)
    {
        $this->baseUri = $baseUri;
        $this->token = $token;
    }

    /**
     * Переопределяет HTTP-клиента.
     *
     * @param \GuzzleHttp\ClientInterface $client
     *
     * @return \Celestial\Api\ApiProvider
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Возвращает HTTP-клиент.
     *
     * @return \GuzzleHttp\ClientInterface
     */
    public function getClient()
    {
        if (!is_null($this->client)) {
            return $this->client;
        }

        return $this->client = new Client([
            'base_uri' => $this->baseUri,
            'headers' => [
                'Accept' => 'application/json',
                'X-Service-Auth' => $this->token,
            ],
        ]);
    }

    /**
     * Возвращает API-токен.
     *
     * @return string | null
     */
    public function token()
    {
        return $this->token;
    }

    /**
     * Возвращает полный URL по отношению к базовому.
     *
     * @param string $url
     *
     * @return string
     */
    public function resolveUrl(string $url)
    {
        return rtrim($this->baseUri, '/').'/'.ltrim($url, '/');
    }

    /**
     * Выполняет HTTP-запрос к удаленному сервису.
     *
     * @param string $method
     * @param string $url
     * @param array  $params = []
     *
     * @return \Celestial\Contracts\Api\ApiResponseContract
     */
    public function request(string $method, string $url, array $params = [])
    {
        if (is_null($this->baseUri) || is_null($this->token)) {
            return $this->dummyResponse();
        }

        try {
            $response = $this->getClient()->request(strtoupper($method), $url, $params);
        } catch (RequestException $e) {
            return $this->transformResponse($e->getResponse());
        }

        return $this->transformResponse($response);
    }

    /**
     * Трансформирует ответ от сервиса в общий интерфейс.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @throws \Celestial\Exceptions\Api\EmptyApiResponseException
     * @throws \Celestial\Exceptions\Api\InvalidJsonResponseException
     *
     * @return \Celestial\Contracts\Api\ApiResponseContract
     */
    public function transformResponse(ResponseInterface $response)
    {
        $apiResponse = new ApiResponse($response->getStatusCode());
        $body = $response->getBody();

        if (empty($body)) {
            throw new EmptyApiResponseException('API Request ended in empty response.');
        }

        $json = json_decode($body, true);

        if (empty($json)) {
            throw new InvalidJsonResponseException('API Request returned invalid JSON response.');
        }

        return $apiResponse->setResponseData($json);
    }

    /**
     * Создает фейковый ответ API.
     *
     * @return \Celestial\Contracts\Api\ApiResponseContract
     */
    public function dummyResponse()
    {
        $response = new ApiResponse(static::HTTP_NOT_FOUND);

        return $response->setResponseData(['error' => 404]);
    }
}
