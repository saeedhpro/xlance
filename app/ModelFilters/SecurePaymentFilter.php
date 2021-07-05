<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class SecurePaymentFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function user($id)
    {
        return $this->where('user_id', '=', $id);
    }

    public function to($id)
    {
        return $this->where('to_id', '=', $id);
    }

    public function term($term)
    {
        return $this->where(function($q) use ($term)
        {
            return $q->where('title', 'LIKE', "%$term%");
        });
    }
}
