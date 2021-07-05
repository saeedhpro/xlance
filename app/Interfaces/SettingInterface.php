<?php


namespace App\Interfaces;


use App\Http\Requests\UpdateSettingRequest;

/**
 * Interface SettingInterface
 * @package App\Interfaces;
 */
interface SettingInterface extends BaseInterface
{
    public function show();
    public function change(UpdateSettingRequest $request);
}
