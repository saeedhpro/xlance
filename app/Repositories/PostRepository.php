<?php


namespace App\Repositories;


use App\Http\Requests\CancelProjectRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\PostCollectionResource;
use App\Interfaces\PostInterface;
use App\Models\CancelProjectRequest as CancelProject;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Project;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PostRepository extends BaseRepository implements PostInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    /**
     * Return all model rows
     * @param array $columns
     * @param string $orderBy
     * @param string $sortBy
     * @return mixed
     */
    public function all($columns = array('*'), $orderBy = 'id', $sortBy = 'desc') {
        /** @var User $user */
        $user = auth()->user();
        $followings = $user->followings;
        $posts = $this->model->whereIn('user_id', $followings->pluck('id'))->orWhere('user_id', '=', $user->id)->get();
        return new PostCollectionResource($posts);
    }

    /**
     * Return all model rows by paginate
     * @param string[] $columns
     * @param string $orderBy
     * @param string $sortBy
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function allByPagination($columns = array('*'), $orderBy = 'id', $sortBy = 'desc', $page = 1, $limit = 10): PostCollectionResource
    {
        /** @var User $user */
        $user = auth()->user();
        $followings = $user->followings;
        $posts = $this->model->orderBy($orderBy, $sortBy)->whereIn('user_id', $followings->pluck('id'))->paginate($page);
        return new PostCollectionResource($posts);
    }

    /**
     * Return all model rows
     * @param $user_id
     * @param array $columns
     * @param string $orderBy
     * @param string $sortBy
     * @return mixed
     */
    public function userAllPosts($user_id, $columns = array('*'), $orderBy = 'id', $sortBy = 'desc')
    {
        return $this->model->where('user_id', '=', $user_id)->get();
    }

    /**
     * Return all model rows by paginate
     * @param $user_id
     * @param string $orderBy
     * @param string $sortBy
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function userAllPostsByPagination($user_id, $orderBy = 'id', $sortBy = 'desc', $page = 1, $limit = 10)
    {
        return $this->model->where('user_id', '=', $user_id)->paginate($page);
    }

    /**
     * Create a model instance
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes) {
        /** @var User $user */
        $user = auth()->user();
        $post = $this->model->create(array(
            'caption' => $attributes['caption'],
            'user_id' => $user->id,
        ));
        if($attributes['image_id']) {
            $post = $this->setImage($post, $attributes['image_id']);
        }
        $post->notifications()->create(array(
            'text' => 'پست ایجاد شد',
            'type' => Notification::POST,
            'user_id' => $user->id,
            'image_id' => $post->media ? $post->media->id : null
        ));
        $users = $user->followers->pluck('id');
        $admins = User::all()->filter(function (User $u){
            return $u->hasRole('admin');
        })->pluck('id');
        $ids = Collection::make($users);
        $ids->push($admins->values());
        $emails = User::all()->whereIn('id', $ids->toArray())->pluck('email');
        $users = User::all()->whereIn('id', $ids->toArray());
        foreach ($users as $user) {
            $user->notifs()->create(array(
                'text' => 'پست ایجاد شد',
                'type' => Notification::POST,
                'user_id' => $user->id,
                'notifiable_id' => $post->id,
                'notifiable_type' => get_class($post),
                'image_id' => $post->media ? $post->media->id : null
            ));
        }
        Notification::sendNotificationToAll($emails->toArray(), 'پست ایجاد شد', 'پست ایجاد شد', null);
        Notification::sendNotificationToUsers($users);
        return $post;
    }

    /**
     * Create a model instance
     * @param array $attributes
     * @param $id
     * @return mixed
     */
    public function update(array $attributes, $id)
    {
        $post = $this->find($id);
        $post->update(array(
            'caption' => $attributes['caption'],
        ));
        if($attributes['new_image_id']) {
            $post = $this->setImage($post, $attributes['new_image_id']);
        }
        return $post;
    }

    private function setImage(Post $post, $image_id)
    {
        $upload = Upload::find($image_id);
        if($upload) {
            $post->media()->delete();
            $post->media()->create([
                'name' => $upload->name,
                'path' => $upload->path,
                'type' => Media::IMAGE_TYPE,
            ]);
        }
        return $post;
    }

    public function like($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $post = $this->findOneOrFail($id);
        $auth->like($post);
        $post->notifications()->create([
            'text' => $auth->first_name . '' . $auth->last_name . ' پست شما را پسندید',
            'type' => Notification::POST,
            'user_id' => $post->user->id,
            'notifiable_id' => $post->id,
            'image_id' => $post->media ? $post->media->id : null
        ]);
        Notification::sendNotificationToUsers(collect([$post->user]));
        return true;
    }

    public function unLike($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $post = $this->findOneOrFail($id);
        $auth->unlike($post);
        return true;
    }

    public function save($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $post = $this->findOneOrFail($id);
        $auth->favorite($post);
        return true;
    }

    public function unSave($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $post = $this->findOneOrFail($id);
        $auth->unfavorite($post);
        return true;
    }

    public function bookmark($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $post = $this->findOneOrFail($id);
        $auth->bookmark($post);
        return true;
    }

    public function unmark($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $post = $this->findOneOrFail($id);
        $auth->unmark($post);
        return true;
    }
}
