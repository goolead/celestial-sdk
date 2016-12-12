<?php

namespace Celestial\Contracts\Api;

interface ApiResponseContract
{
    /**
     * Сохраняет ответ от удаленного сервиса.
     *
     * @param array $responseData
     *
     * @return \Celestial\Api\ApiResponse
     */
    public function setResponseData(array $responseData);

    /**
     * Проверяет, имеется ли какая-либо ошибка в ответе API.
     *
     * @return bool
     */
    public function requestFailed(): bool;

    /**
     * Возвращает HTTP-код ответа.
     *
     * @return int
     */
    public function statusCode(): int;

    /**
     * Возвращает полный ответ API.
     *
     * @return array
     */
    public function response(): array;

    /**
     * Возвращает данные метода API (если присутствуют).
     *
     * @return array | null
     */
    public function data();

    /**
     * Проверяет, выполнился ли API метод успешно (возвращаемое значение зависит от метода).
     *
     * @return bool
     */
    public function success(): bool;

    /**
     * Проверяет, выполнился ли API метод с ошибкой (возвращаемое значение зависит от метода).
     *
     * @return bool
     */
    public function failed(): bool;
}
