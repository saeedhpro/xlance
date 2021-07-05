<?php


namespace App\Repositories;


use App\Interfaces\SkillInterface;
use Illuminate\Database\Eloquent\Model;

class SkillRepository extends BaseRepository implements SkillInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }
}
