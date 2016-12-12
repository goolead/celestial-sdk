<?php

namespace Celestial\Contracts\Services\Webhooks;

interface WebhookContract
{
    /**
     * Возвращает тип сущности, на действия которой подписан вебхук.
     *
     * @return string | null
     */
    public function actorType();

    /**
     * Возвращает ID сущности, на действия которой подписан вебхук.
     *
     * @return int
     */
    public function actorId(): int;

    /**
     * Возвращает тип события, на которое подписан вебхук.
     *
     * @return string | null
     */
    public function event();

    /**
     * Возвращает URL вебхука.
     *
     * @return string | null
     */
    public function url();
}
