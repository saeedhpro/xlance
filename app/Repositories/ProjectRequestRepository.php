<?php


namespace App\Repositories;

use App\Interfaces\ProjectRequestInterface;
use App\Models\Request;
use Illuminate\Database\Eloquent\Model;

class ProjectRequestRepository extends BaseRepository implements ProjectRequestInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function securePayments($id)
    {
        /** @var Request $request */
        $request = $this->findOneOrFail($id);
        return $request->securePayments()->get();
    }
}
