<?php


namespace App\Repositories;


use App\Interfaces\MediaInterface;
use Illuminate\Database\Eloquent\Model;

class MediaRepository extends BaseRepository implements MediaInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    /**
     * Return all model rows
     * @param string $type
     * @return mixed
     */
    public function allByType($type)
    {
        return $this->model->where('type', '=', $type)->get();
    }

    /**
     * Return all model rows
     * @param string $type
     * @param int $page
     * @return mixed
     */
    public function allByTypeByPagination($type, $page = 1)
    {
        return $this->model->where('type', '=', $type)->paginate($page);
    }
}
