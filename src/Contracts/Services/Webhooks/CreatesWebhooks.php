<?php

namespace Celestial\Contracts\Services\Webhooks;

interface CreatesWebhooks
{
    public function createWebhook(string $actorType, int $actorId, string $event, string $url);
}
