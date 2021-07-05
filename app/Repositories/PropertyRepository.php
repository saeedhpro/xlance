<?php


namespace App\Repositories;

use App\Interfaces\PropertyInterface;
use Illuminate\Database\Eloquent\Model;

class PropertyRepository extends BaseRepository implements PropertyInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }
    public function all($columns = array('*'), $orderBy = 'id', $sortBy = 'desc')
    {
        return parent::all($columns, $orderBy, 'asc');
    }
}
