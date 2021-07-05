<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class UserFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function active($active)
    {
        return $active ?
            $this->where('email_verified_at', '!=', null) :
            $this->where('email_verified_at', '==', null) ;
    }

    public function role($role)
    {
        return $this->hasRole($role);
    }

    public function term($term)
    {
        return $this->where(function($q) use ($term)
        {
            return $q->where('first_name', 'LIKE', "%$term%")
                ->orWhere('last_name', 'LIKE', "%$term%")
                ->orWhere('username', 'LIKE', "%$term%")
                ->orWhere('email', 'LIKE', "%$term%");
        });
    }

    public function skills($skills)
    {
        $this->related('skills', function($query) use ($skills) {
            return $query->whereIn('skills.id', $skills);
        });
    }
}
