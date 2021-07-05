<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStateRequest;
use App\Http\Resources\CityCollectionResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\StateResource;
use App\Interfaces\StateInterface;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;

class StateController extends Controller
{
    protected $stateRepository;
    public function __construct(StateInterface $stateRepository)
    {
        $this->stateRepository = $stateRepository;
    }

    public function show($id)
    {
        return new StateResource($this->stateRepository->findOneOrFail($id));
    }

    public function store(StoreStateRequest $request)
    {
        $auth = auth()->user();
        if($auth->hasRole('admin')) {
            $state = $this->stateRepository->create($request->only([
                'country_id',
                'name'
            ]));
            return new StateResource($state);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function destroy(State $state)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->hasRole('admin')) {
            try {
                $this->stateRepository->delete($state->id);
                return \response()->json(['success' => 'استان با موفقیت حذف شد', 'id' => $state->id], 200);
            } catch (\Exception $e) {
                return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function country(State $state)
    {
        return new CountryResource($state->country);
    }

    public function cities(State $state)
    {
        return new CityCollectionResource($state->cities);
    }
}
