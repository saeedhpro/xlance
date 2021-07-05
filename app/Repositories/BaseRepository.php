<?php


namespace App\Repositories;

use App\Interfaces\BaseInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

/**
 * Class BaseRepository
 *
 * @package \App\Repositories
 */
class BaseRepository implements BaseInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Create a model instance
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes)
    {
        return $this->model->create($attributes);
    }

    /**
     * Update a model instance
     * @param array $attributes
     * @param int $id
     * @return mixed
     */
    public function update(array $attributes, $id)
    {
        return $this->find($id)->update($attributes);
    }

    /**
     * Return all model rows
     * @param array $columns
     * @param string $orderBy
     * @param string $sortBy
     * @return mixed
     */
    public function all($columns = array('*'), $orderBy = 'id', $sortBy = 'DESC')
    {
        return $this->model->orderBy($orderBy, $sortBy)->get($columns);
    }

    public function allByPagination($columns = array('*'), $orderBy = 'id', $sortBy = 'DESC', $page = 1, $limit = 10)
    {
        return $this->model->orderBy($orderBy, $sortBy)->paginate($limit);
    }

    /**
     * Delete one by Id
     * @param int $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->model->find($id)->delete();
    }

    /**
     * Find one by ID
     * @param int $id
     * @return mixed
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     * Find one by ID or throw exception
     * @param int $id
     * @return mixed
     */
    public function findOneOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Find based on a different column
     * @param array $data
     * @return mixed
     */
    public function findBy(array $data)
    {
        return $this->model->where($data)->all();
    }

    /**
     * Find one based on a different column
     * @param array $data
     * @return mixed
     */
    public function findOneBy(array $data)
    {
        return $this->model->where($data)->first();
    }

    /**
     * Find one based on a different column or through exception
     * @param array $data
     * @return mixed
     */
    public function findOneByOrFail(array $data)
    {
        return $this->model->where($data)->firstOrFail();
    }


    protected function getNonce() {
        try {
            $nonce = random_int(0, 2 ^ 1024);
        } catch (\Exception $e) {
            $nonce = 1024;
        }
        return $nonce;
    }
}
