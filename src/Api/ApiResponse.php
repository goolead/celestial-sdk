<?php

namespace Celestial\Api;

use Celestial\Contracts\Api\ApiResponseContract;

class ApiResponse implements ApiResponseContract
{
    const HTTP_STATUS_OK = 200;
    const HTTP_STATUS_UNPROCESSABLE_ENTITY = 422;
    const HTTP_STATUS_PAYMENT_REQUIRED = 402;
    const HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var array
     */
    protected $responseData = [];

    /**
     * @param int $statusCode
     */
    public function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Сохраняет ответ от удаленного сервиса.
     *
     * @param array $responseData
     *
     * @return \Celestial\Api\ApiResponse
     */
    public function setResponseData(array $responseData)
    {
        $this->responseData = $responseData;

        return $this;
    }

    /**
     * Проверяет, имеется ли какая-либо ошибка в ответе API.
     *
     * @return bool
     */
    public function requestFailed(): bool
    {
        return $this->statusCode() !== static::HTTP_STATUS_OK;
    }

    /**
     * Возвращает HTTP-код ответа.
     *
     * @return int
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Возвращает полный ответ API.
     *
     * @return array
     */
    public function response(): array
    {
        return $this->responseData;
    }

    /**
     * Возвращает данные метода API (если присутствуют).
     *
     * @return array | null
     */
    public function data()
    {
        return $this->responseData['data'] ?? null;
    }

    /**
     * Проверяет, выполнился ли API метод успешно (возвращаемое значение зависит от метода).
     *
     * @return bool
     */
    public function success(): bool
    {
        return intval($this->responseData['success'] ?? 0) === 1;
    }

    /**
     * Проверяет, выполнился ли API метод с ошибкой (возвращаемое значение зависит от метода).
     *
     * @return bool
     */
    public function failed(): bool
    {
        return !$this->success();
    }
}
