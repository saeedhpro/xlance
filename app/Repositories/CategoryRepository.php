<?php


namespace App\Repositories;


use App\Interfaces\CategoryInterface;
use App\Models\Category;
use App\Models\Image;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;

class CategoryRepository extends BaseRepository implements CategoryInterface
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

    /**
     * Create a model instance
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes) {
        $category = $this->model->create(array(
            'name' => $attributes['name'],
            'type' => $attributes['type'],
            'parent_id' => $attributes['parent_id'],
        ));
        if($attributes['image_id']) {
            $category = $this->setImage($category, $attributes['image_id']);
        }
        return $category;
    }

    public function update(array $attributes, $id)
    {
        $category = $this->find($id);
        $category->update(array(
            'name' => $attributes['name'],
            'parent_id' => $attributes['parent_id'],
        ));
        if($attributes['new_image_id']) {
            $category = $this->setImage($category, $attributes['new_image_id']);
        }
        return $category;
    }

    /**
     * Create a model instance
     * @param Category $category
     * @param $image_id
     * @return mixed
     */
    private function setImage(Category $category, $image_id)
    {
        $upload = Upload::find($image_id);
        if($upload) {
            $category->image()->delete();
            $category->image()->create([
                'name' => $upload->name,
                'path' => $upload->path,
            ]);
        }
        return $category;
    }
}
