<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class ProjectFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function minPrice($min_price)
    {
        return $this->where('min_price', '>=', $min_price);
    }

    public function maxPrice($max_price)
    {
        return $this->where('max_price', '<=', $max_price);
    }

    public function term($term)
    {
        return $this->where(function($q) use ($term)
        {
            return $q->where('title', 'LIKE', "%$term%")
                ->orWhere('description', 'LIKE', "%$term%");
        });
    }

    public function skills($skills)
    {
        $this->related('skills', function($query) use ($skills) {
            return $query->whereIn('skills.id', $skills);
        });
    }
}
