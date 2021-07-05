<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Interfaces\SettingInterface;
use App\Models\User;

class SettingController extends Controller
{
    protected $settingRepository;

    public function __construct(SettingInterface $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    public function show()
    {
        return new SettingResource($this->settingRepository->show());
    }

    public function change(UpdateSettingRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->hasRole('admin')) {
            return new SettingResource($this->settingRepository->change($request));
        } else {
            return $this->accessDeniedResponse();
        }
    }
}
