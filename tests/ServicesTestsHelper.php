<?php

use Celestial\Api\ApiProvider;
use Celestial\Api\ApiResponse;

class ServicesTestsHelper
{
    public static function mockApi($methods = null, $callback = null)
    {
        if ($methods instanceof Closure) {
            $callback = $methods;
            $methods = null;
        }

        if (is_null($methods)) {
            $methods = ['request'];
        }

        $mock = Mockery::mock(ApiProvider::class.'['.implode(',', $methods).']', [
            'https://example.org', 'api-token'
        ]);

        if ($callback instanceof Closure) {
            $callback($mock);
        }

        return $mock;
    }

    public static function toApiResponse(array $data, int $code = 200)
    {
        $response = new ApiResponse($code);

        return $response->setResponseData($data);
    }
}
