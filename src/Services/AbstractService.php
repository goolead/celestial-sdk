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

    /**
     * @param \Celestial\Contracts\Api\ApiProviderContract $api
     *
     * @return \Celestial\Services\AbstractService
     */
    public function setApiProvider(ApiProviderContract $api)
    {
        $this->api = $api;

        return $this;
    }

    /**
     * @return \Celestial\Contracts\Api\ApiProviderContract
     */
    public function getApiProvider()
    {
        return $this->api;
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
