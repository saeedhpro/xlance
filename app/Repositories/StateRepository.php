<?php


namespace App\Repositories;


use App\Interfaces\StateInterface;
use Illuminate\Database\Eloquent\Model;

class StateRepository extends BaseRepository implements StateInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }
}
