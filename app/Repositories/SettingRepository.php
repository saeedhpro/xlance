<?php


namespace App\Repositories;


use App\Http\Requests\UpdateSettingRequest;
use App\Interfaces\SettingInterface;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;

class SettingRepository extends BaseRepository implements SettingInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function show()
    {
        return Setting::all()->first();
    }

    public function change(UpdateSettingRequest $request)
    {
        /** @var Setting $setting */
        $setting = Setting::all()->first();
        $setting->update($request->all());
        return $setting;
    }
}
