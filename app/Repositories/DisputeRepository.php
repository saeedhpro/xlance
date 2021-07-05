<?php


namespace App\Repositories;


use App\Interfaces\DisputeInterface;
use Illuminate\Database\Eloquent\Model;

class DisputeRepository extends BaseRepository implements DisputeInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }
}
