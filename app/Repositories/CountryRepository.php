<?php


namespace App\Repositories;


use App\Interfaces\CountryInterface;
use App\Models\Country;
use Illuminate\Database\Eloquent\Model;

class CountryRepository extends BaseRepository implements CountryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function cities($id)
    {
        /** @var Country $country */
        $country = $this->model->findOrFail($id);
        return $country->cities;
    }

    public function states($id)
    {
        /** @var Country $country */
        $country = $this->model->findOrFail($id);
        return $country->states;
    }
}
