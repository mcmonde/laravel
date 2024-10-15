<?php

namespace App\Traits;

trait PayloadRuleTrait
{
    public function payloadRules(): array
    {
        return [
            'page' => ['nullable', 'integer'],

            'search_global' => ['nullable','string'],

            'search' => ['nullable', 'array'],
            'search.*.key' => ['nullable', 'string'],
            'search.*.s' => ['required_with:search.*.key'],

            'list_orders' => ['nullable', 'array'],
            'list_orders.*.order_by' => ['nullable','string'],
            'list_orders.*.sort_order' => ['required_with:list_orders.*.order_by','in:ASC,DESC'],

            'excluded_id' => ['nullable','array'],
            'excluded_id.*' => ['integer'],

            'show' => ['nullable'],

            'date_column' => ['nullable','string'],
            'date_from' => ['required_with:date_column', 'date'],
            'date_to' => ['required_with:date_from', 'date','after_or_equal:date_from'],
        ];
    }

    public function payloadMessages(): array
    {
        return [
            'search.*.s.required_with' => 'The search s is required when search key is specified.',
            'list_orders.*.sort_order.required_with' => 'The sort order is required when order by is specified.',
            'list_orders.*.sort_order.in' => 'The sort order value should be ASC or DESC.',
        ];
    }
}
