<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCityRequest;
use App\Http\Resources\CityResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\UserCollectionResource;
use App\Interfaces\CityInterface;
use App\Models\City;

class CityController extends Controller
{
    protected $cityRepository;
    public function __construct(CityInterface $cityRepository)
    {
        $this->cityRepository = $cityRepository;
    }

    public function show($id)
    {
        return new CityResource($this->cityRepository->findOneOrFail($id));
    }

    public function users($id)
    {
        return new UserCollectionResource($this->cityRepository->users($id));
    }

    public function store(StoreCityRequest $request)
    {
        $auth = auth()->user();
        if($auth->hasRole('admin')) {
            $city = $this->cityRepository->create($request->only([
                'country_id',
                'state_id',
                'name'
            ]));
            return new CityResource($city);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function country(City $city)
    {
        return new CountryResource($city->country);
    }
}
