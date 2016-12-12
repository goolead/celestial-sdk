<?php

namespace Celestial\Services;

use Celestial\Contracts\Api\ApiProviderContract;
use InvalidArgumentException;

abstract class AbstractService
{
    /**
     * @var \Celestial\Contracts\Api\ApiProviderContract
     */
    protected $api;

    public function __construct(ApiProviderContract $api)
    {
        $this->api = $api;
    }

    public function setApiProvider(ApiProviderContract $api)
    {
        $this->api = $api;

        return $this;
    }

    /**
     * Проверяет корректность переданных данных.
     *
     * @param array $data
     * @param array $fields
     *
     * @throws \InvalidArgumentException
     */
    protected function checkForRequiredFields(array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException('Field "'.$field.'" is requried but not presented in data.');
            }
        }
    }
}