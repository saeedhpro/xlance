<?php


namespace App\Repositories;


use App\Interfaces\EducationInterface;
use Illuminate\Database\Eloquent\Model;

class EducationRepository extends BaseRepository implements EducationInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }
}
