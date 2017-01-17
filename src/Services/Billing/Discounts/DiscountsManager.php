<?php

namespace Celestial\Services\Billing\Discounts;

use Celestial\Contracts\Services\Billing\Discounts\DiscountsManagerContract;
use Celestial\Exceptions\Services\Billing\DiscountWasNotCreatedException;
use Celestial\Services\AbstractService;

class DiscountsManager extends AbstractService implements DiscountsManagerContract
{
    use DiscountsList;

    /**
     * Создает новую скидку.
     *
     * @param array  $attributes
     * @param array  $applyTo    = []
     * @param string $activeTill = null
     *
     * @throws \InvalidArgumentException
     * @throws \Celestial\Exceptions\Services\Billing\DiscountWasNotCreatedException
     *
     * @return \Celestial\Contracts\Services\Billing\Discounts\DiscountContract
     */
    public function create(array $attributes, array $applyTo = [], string $activeTill = null)
    {
        $this->checkForRequiredFields($attributes, [
            'type', 'discount_type', 'entity_type',
            'entity_id', 'value',
        ]);

        if (!isset($attributes['max_value'])) {
            $attributes['max_value'] = $attributes['value'];
        }

        $form = [
            'type' => $attributes['type'],
            'discount_type' => $attributes['discount_type'],
            'entity_type' => $attributes['entity_type'],
            'entity_id' => $attributes['entity_id'],
            'value' => $attributes['value'],
            'max_value' => $attributes['max_value'],
        ];

        if (count($applyTo)) {
            $form['apply_to'] = $applyTo;
        }

        if (!is_null($activeTill)) {
            $form['active_till'] = $activeTill;
        }

        $response = $this->api->request('POST', '/discounts', [
            'form_params' => $form,
        ]);

        if ($response->failed()) {
            throw new DiscountWasNotCreatedException('Unable to create discount. Response status: '.$response->statusCode());
        }

        return new Discount($this->api, $response->data());
    }

    /**
     * Возвращает список всех скидок.
     *
     * @return array
     */
    public function get()
    {
        return $this->loadDiscountsFrom($this->api, '/discounts');
    }

    /**
     * Выполняет поиск скидки по ID.
     *
     * @param int $id
     *
     * @return \Celestial\Contracts\Services\Billing\Discounts\DiscountContract|null
     */
    public function find(int $discountId)
    {
        $request = $this->api->request('GET', '/discounts/'.$discountId);

        if ($request->failed()) {
            return;
        }

        return new Discount($this->api, $request->data());
    }
}
