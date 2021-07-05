<?php


namespace App\Repositories;


use App\Interfaces\DisputeMessageInterface;
use Illuminate\Database\Eloquent\Model;

class DisputeMessageRepository extends BaseRepository implements DisputeMessageInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }
}
