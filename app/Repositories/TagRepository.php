<?php


namespace App\Repositories;


use App\Interfaces\TagInterface;
use Illuminate\Database\Eloquent\Model;

class TagRepository extends BaseRepository implements TagInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }
}
