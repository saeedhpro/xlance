<?php


namespace App\Repositories;


use App\Interfaces\CityInterface;
use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Model;

class CityRepository extends BaseRepository implements CityInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function users($id)
    {
        /** @var City $city */
        $city = $this->model->findOrFail($id);
        return $city->users;
    }
}
