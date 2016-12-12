<?php

namespace Celestial\Services\Webhooks;

use Celestial\Contracts\Services\Webhooks\WebhookContract;

class Webhook implements WebhookContract
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Возвращает тип сущности, на действия которой подписан вебхук.
     *
     * @return string | null
     */
    public function actorType()
    {
        return $this->data['actor_type'] ?? null;
    }

    /**
     * Возвращает ID сущности, на действия которой подписан вебхук.
     *
     * @return int
     */
    public function actorId(): int
    {
        return intval($this->data['actor_id'] ?? 0);
    }

    /**
     * Возвращает тип события, на которое подписан вебхук.
     *
     * @return string | null
     */
    public function event()
    {
        return $this->data['event'] ?? null;
    }

    /**
     * Возвращает URL вебхука.
     *
     * @return string | null
     */
    public function url()
    {
        return $this->data['url'] ?? null;
    }
}
