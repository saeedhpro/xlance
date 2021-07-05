<?php


namespace App\Repositories;


use App\Interfaces\ArticleInterface;
use App\Models\Article;
use App\Models\Image;
use App\Models\Tag;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;

class ArticleRepository extends BaseRepository implements ArticleInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function allByPagination($columns = array('*'), $orderBy = 'id', $sortBy = 'DESC', $page = 1, $limit = 10)
    {
        return $this->model->orderBy('id', 'DESC')->paginate($limit);
    }

    /**
     * Create a model instance
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes) {
        $article = $this->model->create(array(
            'title' => $attributes['title'],
            'body' => $attributes['body'],
            'category_id' => $attributes['category_id'],
            'user_id' => auth()->user()->id,
        ));
//        $tags = $attributes['tags'];
//        if($tags) {
//            $this->handleTags($article, $tags);
//        }
        if($attributes['image_id']) {
            $article = $this->setImage($article, $attributes['image_id']);
        }
        return $article;
    }

    public function update(array $attributes, $id)
    {
        $article = $this->find($id);
        $article->update(array(
            'title' => $attributes['title'],
            'body' => $attributes['body'],
            'category_id' => $attributes['category_id'],
        ));
//        $tags = $attributes['tags'];
//        if($tags) {
//            $this->handleTags($article, $tags);
//        }
        if($attributes['new_image_id']) {
            $article = $this->setImage($article, $attributes['new_image_id']);
        }
        return $article;
    }

    private function setImage(Article $article, $image_id)
    {
        $upload = Upload::find($image_id);
        if($upload) {
            $article->thumbnail()->delete();
            /** @var Image $image */
            $article->thumbnail()->create([
                'name' => $upload->name,
                'path' => $upload->path,
            ]);
        }
        return $article;
    }

    private function handleTags(Article $article, array $tags)
    {
        foreach($tags as $tag){
            Tag::firstOrCreate(['name' => $tag])->save();
        }
        $tags_id = Tag::whereIn('name', $tags)->pluck('id');
        $article->tags()->sync($tags_id);
    }

    public function search($term)
    {
        $articles = $this->model->query()
            ->where('title', 'LIKE', '%'.$term.'%')
            ->orWhere('body', 'LIKE', '%'.$term.'%')->get();
        return $articles;
    }
}
