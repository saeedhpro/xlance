<?php


namespace App\Repositories;

use App\Http\Requests\StoreSecurePaymentRequest;
use App\Interfaces\SecurePaymentInterface;
use Illuminate\Database\Eloquent\Model;

class SecurePaymentRepository extends BaseRepository implements SecurePaymentInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function store(StoreSecurePaymentRequest $request)
    {

    }
}
