<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCountryRequest;
use App\Http\Resources\CityCollectionResource;
use App\Http\Resources\CountryCollectionResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\StateCollectionResource;
use App\Interfaces\CountryInterface;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    protected $countryRepository;
    public function __construct(CountryInterface $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    public function all()
    {
        return new CountryCollectionResource(Country::all());
    }

    public function show($id)
    {
        return new CountryResource($this->countryRepository->findOneOrFail($id));
    }

    public function cities($id)
    {
        return new CityCollectionResource($this->countryRepository->cities($id));
    }

    public function states($id)
    {
        return new StateCollectionResource($this->countryRepository->states($id));
    }

    public function store(StoreCountryRequest $request)
    {
        $auth = auth()->user();
        if($auth->hasRole('admin')) {
            $country = $this->countryRepository->create($request->only([
                'name'
            ]));
            return new CountryResource($country);
        } else {
            return $this->accessDeniedResponse();
        }
    }
}
