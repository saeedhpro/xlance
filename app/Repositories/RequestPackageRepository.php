<?php


namespace App\Repositories;


use App\Interfaces\RequestPackageInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RequestPackageRepository extends BaseRepository implements RequestPackageInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function ownRequestPackage()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $this->userRequestPackage($auth->id);
    }

    public function userRequestPackage($id)
    {
        /** @var User $user */
        $user = $this->findOneOrFail($id);
        return $user->requestPackage()->get();
    }
}
