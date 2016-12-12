<?php

namespace Celestial\Services\Webhooks\Traits;

use Celestial\Exceptions\Services\Webhooks\UnableToCreateWebhookException;
use Celestial\Services\Webhooks\Webhook;

trait Webhooks
{
    public function createWebhook(string $actorType, int $actorId, string $event, string $url)
    {
        $response = $this->api->request('POST', '/webhooks', [
            'form_params' => [
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'event' => $event,
                'url' => $url,
            ],
        ]);

        if ($response->failed()) {
            throw new UnableToCreateWebhookException('Service answered with status '.$response->statusCode().'.');
        }

        return new Webhook($response->data());
    }
}
